<?php

declare(strict_types=1);

namespace TinyFramework\Opcache\Http\Controllers;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use TinyFramework\Http\Response;

class OpcacheController
{
    public function status(): Response
    {
        return Response::json([
            'error' => 0,
            'data' => array_merge(
                ['node' => node()],
                opcache_get_status(false)
            )
        ]);
    }

    public function preload(): Response
    {
        $preloads = config('opcache.preloads');
        $excludes = config('opcache.excludes');

        foreach ($preloads as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $directory = new RecursiveDirectoryIterator($path);
            $iterator = new RecursiveIteratorIterator($directory);
            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                try {
                    if (!$file->isFile()) {
                        continue;
                    }
                    if ($file->getExtension() !== 'php') {
                        continue;
                    }
                    foreach ($excludes as $exclude) {
                        if (str_starts_with($file->getPathname(), $exclude)) {
                            continue 2;
                        }
                    }
                    if (opcache_is_script_cached($file->getPathname())) {
                        continue;
                    }
                    opcache_compile_file($file->getPathname());
                } catch (\Throwable $e) {
                }
            }
        }
        return Response::json([
            'error' => 0,
            'data' => [
                'node' => node()
            ],
        ]);
    }

    public function clear(): Response
    {
        if (!opcache_reset()) {
            return Response::json([
                'error' => 1,
                'data' => 'Could not clear opcache store.',
            ], 500);
        }
        return Response::json([
            'error' => 0,
            'data' => [
                'node' => node()
            ],
        ]);
    }
}
