### Deploy project using single Laravel Envoy script
> *Note*  This script HAVE DOWNTIME while deploy is executed!

You can deploy a project with a single laravel Envoy script, assuming you have a single project structure:
```
* -- /var/www/turismo
          |---------- app
          |---------- ...
          |---------- routes
          |---------- ...
          |---------- .env
          |---------- storage
          |---------- ...
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

    $repo = env('DEPLOY_REPO');

    if (!isset($baseDir)) {
        $baseDir = env('DEPLOY_BASE_DIR');
    }

    if (!isset($branch)) {
        throw new Exception('--branch must be specified');
    }

    function logMessage($message) {
        return "echo '\033[32m" .$message. "\033[0m';\n";
    }
@endsetup

@servers(['prod' => env('DEPLOY_USER').'@'.env('DEPLOY_SERVER')])

@task('rollback', ['on' => 'prod', 'confirm' => true])
    {{ logMessage("Rolling back") }}
    cd {{ $baseDir }}
    git reset --hard HEAD~1

    {{ logMessage("Updating composer") }}
    rm -rf vendor
    composer install --no-interaction --quiet --prefer-dist --optimize-autoloader

    {{ logMessage("Updating assets") }}
    rm -rf node_modules
    npm install --silent --no-progress
    npm run prod --silent --no-progress

    {{ logMessage("Building cache") }}
    php {{ $baseDir }}/artisan route:cache

    php {{ $baseDir }}/artisan config:cache

    php {{ $baseDir }}/artisan view:cache

    {{ logMessage("Rollback complete") }}
@endtask

@story('deploy', ['on' => 'prod'])
    down
    git
    composer
    npm_install
    npm_run_prod
    set_permissions
    cache
    migrate
    up
@endstory

@task('down')
    {{ logMessage("Putting application in maintenance mode") }}
    php {{ $baseDir }}/artisan down --message="We're doing some maintenance right now, please come back in a few moments"
@endtask

@task('up')
    {{ logMessage("Going linve now") }}
    php {{ $baseDir }}/artisan up
@endtask

@task('git')
    {{ logMessage("Cloning repository") }}

    cd {{ $baseDir }}
    git fetch origin
    git reset --hard origin/{{ $branch }}
@endtask

@task('composer')
    {{ logMessage("Running composer") }}

    cd {{ $baseDir }}
    rm -rf vendor
    composer install --no-interaction --quiet --prefer-dist --optimize-autoloader
@endtask

@task('npm_install')
    {{ logMessage("NPM install") }}

    cd {{ $baseDir }}
    rm -rf node_modules
    npm install --silent --no-progress
@endtask

@task('npm_run_prod')
    {{ logMessage("NPM run prod") }}

    cd {{ $baseDir }}

    npm run prod --silent --no-progress
@endtask

@task('set_permissions')
    # Set dir permissions
    {{ logMessage("Set permissions") }}
    chown -R root:www-data {{ $baseDir }}
    chmod -R ug+rwx {{ $baseDir }}/storage {{ $baseDir }}/bootstrap/cache
@endtask

@task('cache')
    {{ logMessage("Building cache") }}

    php {{ $baseDir }}/artisan route:cache

    php {{ $baseDir }}/artisan config:cache

    php {{ $baseDir }}/artisan view:cache
@endtask

@task('migrate')
    {{ logMessage("Running migrations") }}

    php {{ $baseDir }}/artisan migrate --force
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

Now, having a empty project folder in the server, ej `/var/www/turismo` you can run:
```bash
git init
git remote add origin gitrepourlhere
```

now you must fill `.env` file.

From your computer you can run deploys using this command:
```bash
envoy run deploy --branch=master
```

If you need do some rollback use:
```bash
envoy run rollback --branch=master
```