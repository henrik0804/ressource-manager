<?php

declare(strict_types=1);

test('bootstrap script includes required steps', function (): void {
    $composerPath = base_path('composer.json');
    $composer = json_decode(
        file_get_contents($composerPath),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    $scripts = $composer['scripts']['bootstrap'] ?? null;

    expect($scripts)->toEqual([
        'composer install',
        'npm install',
        '@php -r "file_exists(\'.env\') || copy(\'.env.example\', \'.env\');"',
        '@php artisan key:generate',
        '@php -r "file_exists(\'database/database.sqlite\') || touch(\'database/database.sqlite\');"',
        '@php artisan migrate:fresh --seed',
    ]);
});
