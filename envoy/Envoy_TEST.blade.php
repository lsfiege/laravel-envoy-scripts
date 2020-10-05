@include('vendor/autoload.php')

@setup
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);

    try {
        $dotenv->load();
        $dotenv->required(['DEPLOY_USER_TEST', 'DEPLOY_SERVER_TEST', 'DEPLOY_BASE_DIR_TEST', 'DEPLOY_REPO'])->notEmpty();
    } catch ( Exception $e )  {
        echo $e->getMessage();
    }

    $user = env('DEPLOY_USER_TEST');
    $repo = env('DEPLOY_REPO');

    if (!isset($baseDir)) {
        $baseDir = env('DEPLOY_BASE_DIR_TEST');
    }

    $branchOrTag = env('CI_COMMIT_TAG');
    if (!$branchOrTag) {
        $branchOrTag = env('CI_COMMIT_BRANCH', 'master');
    }

    $releaseDir = $baseDir . '/releases';
    $currentDir = $baseDir . '/current';
    $release = date('YmdHis');
    $currentReleaseDir = $releaseDir . '/' . $release;

    function logMessage($message) {
        return "echo '\033[32m" .$message. "\033[0m';\n";
    }
@endsetup

@import('envoy/Envoy_COMMON.blade.php')

@servers(['web' => env('DEPLOY_USER_TEST').'@'.env('DEPLOY_SERVER_TEST')])

@story('deploy', ['on' => 'web'])
    git
    composer
    update_symlinks
    migrate_release
    set_permissions
    reload_php
    reload_supervisor
    clean_old_releases
@endstory

@finished
    echo "Envoy deployment script finished.\r\n";
@endfinished
