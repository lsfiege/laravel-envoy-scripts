## Deploy project using zero downtime configuration with Laravel Envoy script
For use zero downtime deploys, you need to structure project folder in a different way:
```
* -- /var/www/turismo
          |---------- current --> /var/www/turismo/releases/latestrelease
          |---------- .env
          |---------- releases
          |---------- storage
```

Also you need to update the virtual host configuration to point to `/var/www/turismo/current/public`

To achieve this folder structure, we use a [Laravel Envoy](https://laravel.com/docs/envoy) script, you need install laravel envoy running:
```bash
composer global require laravel/envoy
```

Now add this variables into your `.env` file
```dotenv
DEPLOY_USER=serveruser
DEPLOY_SERVER=ip.of.server.here
DEPLOY_BASE_DIR=/var/www/turismo
DEPLOY_REPO=git@gitlab.com:moldesarrollos/turismomisiones-laravel.git
```

> *Note: you must have a ssh key with access to the pointed server into your `.ssh/config` file, and the repo url must be ssh also*

Now add an `Envoy.blade.php` on the project root folder, you can comment and uncomment sentences what do you need
```blade
@setup
    require __DIR__.'/vendor/autoload.php';

    $dotenv = Dotenv\Dotenv::create(__DIR__);

    try {
        $dotenv->load();
        $dotenv->required(['DEPLOY_USER', 'DEPLOY_SERVER', 'DEPLOY_BASE_DIR', 'DEPLOY_REPO'])->notEmpty();
    } catch ( Exception $e )  {
        echo $e->getMessage();
    }

    $user = env('DEPLOY_USER');
    $repo = env('DEPLOY_REPO');

    if (!isset($baseDir)) {
        $baseDir = env('DEPLOY_BASE_DIR');
    }

    if (!isset($branch)) {
        throw new Exception('--branch must be specified');
    }

    $releaseDir = $baseDir . '/releases';
    $currentDir = $baseDir . '/current';
    $release = date('YmdHis');
    $currentReleaseDir = $releaseDir . '/' . $release;

    function logMessage($message) {
        return "echo '\033[32m" .$message. "\033[0m';\n";
    }
@endsetup

@servers(['prod' => env('DEPLOY_USER').'@'.env('DEPLOY_SERVER')])

@task('rollback', ['on' => 'prod', 'confirm' => true])
    {{ logMessage("Rolling back...") }}
    cd {{ $releaseDir }}
    ln -nfs {{ $releaseDir }}/$(find . -maxdepth 1 -name "20*" | sort  | tail -n 2 | head -n1) {{ $baseDir }}/current
    {{ logMessage("Rolled back!") }}

    {{ logMessage("Rebuilding cache") }}
    php {{ $currentDir }}/artisan route:cache

    php {{ $currentDir }}/artisan config:cache

    php {{ $currentDir }}/artisan view:cache
    {{ logMessage("Rebuilding cache completed") }}

    echo "Rolled back to $(find . -maxdepth 1 -name "20*" | sort  | tail -n 2 | head -n1)"
@endtask

@task('init', ['on' => 'prod', 'confirm' => true])
if [ ! -d {{ $baseDir }}/current ]; then
    cd {{ $baseDir }}

    git clone {{ $repo }} --branch={{ $branch }} --depth=1 -q {{ $release }}
    {{ logMessage("Repository cloned") }}

    mv {{ $release }}/storage {{ $baseDir }}/storage
    ln -nfs {{ $baseDir }}/storage {{ $release }}/storage
    ln -nfs {{ $baseDir }}/storage/public {{ $release }}/public/storage
    {{ logMessage("Storage directory set up") }}

    cp {{ $release }}/.env.example {{ $baseDir }}/.env
    ln -nfs {{ $baseDir }}/.env {{ $release }}/.env
    {{ logMessage("Environment file set up") }}

    chown -R {{ $user }}:www-data {{ $baseDir }}/storage
    chmod -R ug+rwx {{ $baseDir }}/storage

    rm -rf {{ $release }}
    {{ logMessage("Deployment path initialised. Run 'envoy run deploy' now.") }}
else
    {{ logMessage("Deployment path already initialised (current symlink exists)!") }}
fi
@endtask

@story('deploy', ['on' => 'prod'])
    git
    composer
    npm_install
    npm_run_prod
    update_symlinks
    set_permissions
    cache
    migrate
    clean_old_releases
@endstory

@task('git')
    {{ logMessage("Cloning repository") }}

    git clone {{ $repo }} --branch={{ $branch }} --depth=1 -q {{ $currentReleaseDir }}
@endtask

@task('composer')
    {{ logMessage("Running composer") }}

    cd {{ $currentReleaseDir }}

    composer install --no-interaction --quiet --no-dev --prefer-dist --optimize-autoloader
@endtask

@task('npm_install')
    {{ logMessage("NPM install") }}

    cd {{ $currentReleaseDir }}

    npm install --silent --no-progress
@endtask

@task('npm_run_prod')
    {{ logMessage("NPM run prod") }}

    cd {{ $currentReleaseDir }}

    npm run prod --silent --no-progress
@endtask

@task('update_symlinks')
    {{ logMessage("Updating symlinks") }}

    # Remove the storage directory and replace with persistent data
    {{ logMessage("Linking storage directory") }}
    rm -rf {{ $currentReleaseDir }}/storage;
    cd {{ $currentReleaseDir }};
    ln -nfs {{ $baseDir }}/storage {{ $currentReleaseDir }}/storage;
    ln -nfs {{ $baseDir }}/storage/app/public {{ $currentReleaseDir }}/public/storage

    # Remove the public uploads directory and replace with persistent data
#    {{ logMessage("Linking uploads directory") }}
#    rm -rf {{ $currentReleaseDir }}/public/uploads
#    cd {{ $currentReleaseDir }}/public
#    ln -nfs {{ $baseDir }}/uploads {{ $currentReleaseDir }}/uploads;

    # Import the environment config
    {{ logMessage("Linking .env file") }}
    cd {{ $currentReleaseDir }};
    ln -nfs {{ $baseDir }}/.env .env;

    # Symlink the latest release to the current directory
    {{ logMessage("Linking current release") }}
    ln -nfs {{ $currentReleaseDir }} {{ $currentDir }};
@endtask

@task('set_permissions')
    # Set dir permissions
    {{ logMessage("Set permissions") }}
    cd {{ $baseDir }}
    chown -R {{ $user }}:www-data current
    chmod -R ug+rwx current/storage current/bootstrap/cache
    chown -R {{ $user }}:www-data {{ $currentReleaseDir }}
@endtask

@task('cache')
    {{ logMessage("Building cache") }}

    php {{ $currentDir }}/artisan route:cache

    php {{ $currentDir }}/artisan config:cache

    php {{ $currentDir }}/artisan view:cache
@endtask

@task('migrate')
    {{ logMessage("Running migrations") }}

    php {{ $currentReleaseDir }}/artisan migrate --force
@endtask

@task('clean_old_releases')
    # Delete all but the 5 most recent releases
    {{ logMessage("Cleaning old releases") }}
    cd {{ $releaseDir }}
    ls -dt {{ $releaseDir }}/* | tail -n +6 | xargs -d "\n" rm -rf;
@endtask

@task('reload_services')
    # Reload Services
#    {{ logMessage("Restarting service supervisor") }}
#    sudo supervisorctl restart all
#    {{ logMessage("Reloading php") }}
#    sudo systemctl restart php7.3-fpm
@endtask

@finished
    echo "Envoy deployment script finished.\r\n";
@endfinished
```

Now, having an empty project folder in the server, ej `/var/www/turismo` you can run:
```bash
envoy run init --branch=master
```

now ssh into the server and fill `.env` file.

From your computer you can run deploys using this command:
```bash
envoy run deploy --branch=master
```

If you need do some rollback use:
```bash
envoy run rollback --branch=master
```

> *Note: you may update cron and supervisor configurations to point to `/var/www/turismo/current/` directory*