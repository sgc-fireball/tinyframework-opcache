<?php

declare(strict_types=1);

return [
    'preloads' => [
        root_dir() . '/app',
        root_dir() . '/config',
        root_dir() . '/public',
        root_dir() . '/resources/views',
        root_dir() . '/routes',
        root_dir() . '/vendor/sgc-fireball',
    ],
    'excludes' => [
        root_dir() . '/vendor/sgc-fireball/tinyframework/src/Files/',
        root_dir() . '/vendor/sgc-fireball/tinyframework/vendor/',
    ],
    'urls' => explode(',', env('APP_URLS', '')),
];
