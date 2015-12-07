<?php
require 'recipe/common.php';
require 'Build/Packages/deployphp/recipes/recipes/local.php';
require 'Build/Packages/deployphp/recipes/recipes/rsync.php';

server(getenv('STAGE'), getenv('SERVER'))
    ->user(getenv('USER'))
    ->env('deploy_path', getenv('DEPLOY_PATH'))
    ->env('branch', getenv('BRANCH'))
    ->env('local_release_path', __DIR__ . '/Build/tmp/release/' . getenv('BUILD'))
    ->env('rsync_src', __DIR__ . '/Build/tmp/release/' . getenv('BUILD'))
    ->env('rsync_dest', '{{release_path}}')
    ->stage(getenv('STAGE'))
    ->identityFile('~/.ssh/id_rsa.pub', '~/.ssh/id_rsa', '');

set('repository', getenv('REPOSITORY'));
set('shared_dirs', [getenv('SHARED_DIRS')]);
set('writable_dirs', [getenv('WRITABLE_DIRS')]);
set('rsync', [
    'exclude' => [
        '.git',
        '.gitignore',
        'deploy.php',
        'phpci.yml',
        'upload/',
    ],
    'exclude-file' => false,
    'include' => [],
    'include-file' => false,
    'filter' => [],
    'filter-file' => false,
    'filter-perdir' => false,
    'flags' => 'rz',
    'options' => ['delete'],
    'timeout' => 120,
]);

task('deploy:uploadFolder', function () {
    try {
        run("cd {{deploy_path}}/shared && if [ ! -d upload ]; then mkdir upload; fi");
        run('cd {{release_path}} && ln -s {{deploy_path}}/shared/upload upload');
        writeln('Successfully created upload folder.');
    } catch (\Exception $e) {
        throw new $e;
    }
});

/**
 * Main task
 */
task('deploy', [
    'deploy:prepare',
    'deploy:release',
    'local:update_code',
    'local:vendors',
    'rsync',
    'deploy:symlink',
    'deploy:uploadFolder',
    'cleanup',
])->desc('Deployed the {{ branch }} branch of your project');