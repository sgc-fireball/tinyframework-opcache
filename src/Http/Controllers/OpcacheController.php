<?php declare(strict_types=1);

namespace TinyFramework\Opcache\Http\Controllers;

use SplFileInfo;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use TinyFramework\Http\Response;

class OpcacheController
{

    public function status()
    {
        return Response::json([
            'error' => 0,
            'data' => opcache_get_status(false),
        ]);
    }

    public function preload()
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
            'data' => null,
        ]);
    }

    public function clear()
    {
        if (!opcache_reset()) {
            return Response::json([
                'error' => 1,
                'data' => 'Could not clear opcache store.',
            ], 500);
        }
        return Response::json([
            'error' => 0,
            'data' => null,
        ]);
    }

}
