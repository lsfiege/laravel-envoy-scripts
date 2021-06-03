@setup
    require __DIR__.'/vendor/autoload.php';

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);

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

    $branchOrTag = env('CI_COMMIT_TAG');
    if (!$branchOrTag) {
        $branchOrTag = env('CI_COMMIT_BRANCH', 'master');
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
    npm install --silent --no-progress > /dev/null
    npm run prod --silent --no-progress > /dev/null

    {{ logMessage("Deleting node_modules folder") }}
    rm -rf node_modules

    {{ logMessage("Building cache") }}

    php {{ $baseDir }}/artisan optimize

    {{ logMessage("Rollback complete") }}
@endtask

@story('deploy', ['on' => 'prod'])
    git
    composer
    migrate
    npm_install
    npm_run_prod
    set_permissions
    reload_services
    cache
@endstory

@task('git')
    {{ logMessage("Cloning repository") }}

    cd {{ $baseDir }}
    git fetch origin
    git reset --hard origin/{{ $branchOrTag }}
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
    npm install --silent --no-progress > /dev/null
@endtask

@task('npm_run_prod')
    {{ logMessage("NPM run prod") }}

    cd {{ $baseDir }}

    npm run prod --silent --no-progress > /dev/null
@endtask

@task('set_permissions')
    # Set dir permissions
    {{ logMessage("Set permissions") }}
    sudo chown -R root:www-data {{ $baseDir }}
    sudo chmod -R ug+rwx {{ $baseDir }}/storage {{ $baseDir }}/bootstrap/cache
@endtask

@task('cache')
    {{ logMessage("Building cache") }}

    php {{ $baseDir }}/artisan optimize
@endtask


@task('migrate', ['on' => 'prod', 'confirm' => true])
    {{ logMessage("Running migrations") }}

    php {{ $baseDir }}/artisan migrate --force
@endtask

@task('migrate_rollback', ['on' => 'prod', 'confirm' => true])
    {{ logMessage("Rolling back migrations") }}

    php {{ $baseDir }}/artisan migrate:rollback --force
@endtask

@task('migrate_status', ['on' => 'prod'])
    php {{ $baseDir }}/artisan migrate:status
@endtask

@task('reload_services', ['on' => 'prod'])
    # Reload Services
    {{ logMessage("Restarting service supervisor") }}
    sudo supervisorctl restart all

    {{ logMessage("Reloading php") }}
    sudo systemctl reload php7.3-fpm
@endtask


@finished
    echo "Envoy deployment script finished.\r\n";
@endfinished