<?php declare(strict_types=1);

return [
    'preloads' => [
        root_dir() . '/app',
        root_dir() . '/config',
        root_dir() . '/resources/views',
        root_dir() . '/routes',
        root_dir() . '/vendor/sgc-fireball'
    ],
    'excludes' => [
        root_dir() . '/vendor/sgc-fireball/tinyframework/src/Files/console.php',
        root_dir() . '/vendor/sgc-fireball/tinyframework/src/Files/swoole.php',
    ]
];
