<?php

require 'recipe/common.php';
require 'Packages/Libraries/deployphp/recipes/recipes/local.php';
require 'Packages/Libraries/deployphp/recipes/recipes/rsync.php';

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
        'Build/tmp'

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

task('deploy:createSymlinks', function () {
    try {
        run('mkdir -p {{release_path}}/Configuration/Production');
        run('cd {{release_path}}/Configuration/Production && ln -s {{deploy_path}}/shared/Configuration/Production/Settings.yaml');
        writeln('Successfully added symlink to Settings.yaml');

        run('cd {{release_path}}/Data && ln -s {{deploy_path}}/shared/Data/Persistent');
        writeln('Successfully added symlink to Data folder');
    } catch (\Exception $e) {
        throw new $e;
    }
});

task('deploy:flowcommands', function() {
   try {
       run('cd {{deploy_path}}');
       run('FLOW_CONTEXT=Production ./flow doctrine:migrate');
       run('FLOW_CONTEXT=Production ./flow resource:publish');
       run('FLOW_CONTEXT=Production ./flow flow:cache:flush');
       run('FLOW_CONTEXT=Production ./flow flow:help');
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
    'deploy:createSymlinks',
//    'deploy:flowcommands',
    'deploy:symlink',
    'cleanup',
])->desc('Deployed the {{ branch }} branch of your project');