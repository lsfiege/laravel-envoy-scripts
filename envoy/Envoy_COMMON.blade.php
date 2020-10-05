@task('init', ['on' => 'web', 'confirm' => true])
    if [ ! -d {{ $baseDir }}/current ]; then
        cd {{ $baseDir }}

        git clone {{ $repo }} --branch={{ $branchOrTag }} --depth=1 -q {{ $release }}
        {{ logMessage("Repository cloned") }}

        mv {{ $release }}/storage {{ $baseDir }}/storage
        ln -nfs {{ $baseDir }}/storage {{ $release }}/storage
        ln -nfs {{ $baseDir }}/storage/public {{ $release }}/public/storage
        {{ logMessage("Storage directory set up") }}

        cp {{ $release }}/.env.example {{ $baseDir }}/.env
        ln -nfs {{ $baseDir }}/.env {{ $release }}/.env
        {{ logMessage("Environment file set up") }}

        sudo chown -R {{ $user }}:www-data {{ $baseDir }}/storage
        sudo chmod -R ug+rwx {{ $baseDir }}/storage

        rm -rf {{ $release }}
        {{ logMessage("Deployment path initialised. Run 'envoy run deploy' now.") }}
    else
        {{ logMessage("Deployment path already initialised (current symlink exists)!") }}
    fi
@endtask

@task('rollback', ['on' => 'web', 'confirm' => true])
    {{ logMessage("Rolling back...") }}
    cd {{ $releaseDir }}
    ln -nfs {{ $releaseDir }}/$(find . -maxdepth 1 -name "20*" | sort  | tail -n 2 | head -n1) {{ $baseDir }}/current
    {{ logMessage("Rolled back!") }}

    {{ logMessage("Rebuilding cache") }}
    php {{ $currentDir }}/artisan optimize
    {{ logMessage("Rebuilding cache completed") }}

    echo "Rolled back to $(find . -maxdepth 1 -name "20*" | sort  | tail -n 2 | head -n1)"
@endtask

@task('git')
    {{ logMessage("Cloning repository") }}

    git clone {{ $repo }} --branch={{ $branchOrTag }} --depth=1 -q {{ $currentReleaseDir }}
@endtask

@task('composer')
    {{ logMessage("Running composer") }}

    cd {{ $currentReleaseDir }}

    composer install --no-interaction --quiet --no-dev --prefer-dist --optimize-autoloader
@endtask

@task('npm_install')
    {{ logMessage("NPM install") }}

    cd {{ $currentReleaseDir }}

    npm install --silent --no-progress > /dev/null
@endtask

@task('npm_run_prod')
    {{ logMessage("NPM run prod") }}

    cd {{ $currentReleaseDir }}

    npm run prod --silent --no-progress > /dev/null

    {{ logMessage("Deleting node_modules folder") }}
    rm -rf node_modules
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

    sudo chown -R {{ $user }}:www-data {{ $baseDir }}
    sudo chmod -R ug+rwx {{ $baseDir }}/storage
    cd {{ $baseDir }}
    sudo chown -R {{ $user }}:www-data current
    sudo chmod -R ug+rwx current/storage current/bootstrap/cache
    sudo chown -R {{ $user }}:www-data {{ $currentReleaseDir }}
@endtask

@task('cache')
    {{ logMessage("Building cache") }}

    php {{ $currentDir }}/artisan optimize
@endtask

@task('clean_old_releases')
    # Delete all but the 5 most recent releases
    {{ logMessage("Cleaning old releases") }}
    cd {{ $releaseDir }}
    ls -dt {{ $releaseDir }}/* | tail -n +6 | xargs -d "\n" rm -rf;
@endtask

@task('migrate_release', ['on' => 'web', 'confirm' => false])
    {{ logMessage("Running migrations") }}

    php {{ $currentReleaseDir }}/artisan migrate --force
@endtask

@task('migrate', ['on' => 'web', 'confirm' => true])
    {{ logMessage("Running migrations") }}

    php {{ $currentDir }}/artisan migrate --force
@endtask

@task('migrate_rollback', ['on' => 'web', 'confirm' => true])
    {{ logMessage("Rolling back migrations") }}

    php {{ $currentDir }}/artisan migrate:rollback --force
@endtask

@task('migrate_status', ['on' => 'web'])
    php {{ $currentDir }}/artisan migrate:status
@endtask

@task('reload_php', ['on' => 'web'])
    {{ logMessage("Reloading php") }}
    sudo systemctl reload php7.3-fpm
@endtask

@task('reload_supervisor', ['on' => 'web'])
    {{ logMessage("Restarting service supervisor") }}
    sudo supervisorctl restart all
@endtask
