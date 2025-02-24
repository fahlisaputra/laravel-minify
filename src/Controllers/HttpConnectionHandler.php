<?php

namespace Fahlisaputra\Minify\Controllers;

use Fahlisaputra\Minify\Helpers\CSS;
use Fahlisaputra\Minify\Helpers\Javascript;
use Illuminate\Support\Str;

class HttpConnectionHandler
{
    public function __invoke($file)
    {
        // Load configuration
        $config = [
            'js_insert_semicolon' => (bool) config('minify.insert_semicolon.js', true),
            'css_insert_semicolon' => (bool) config('minify.insert_semicolon.css', true),
            'obfuscate' => (bool) config('minify.obfuscate', false),
            'enabled' => (bool) config('minify.assets_enabled', true),
            'storage' => config('minify.assets_storage', 'resources'),
            'buildStorage' => config('minify.assets_build_storage', '/public/assets/_minify'),
        ];

        $css = new CSS();
        $js = new Javascript();

        $file = ltrim($file, '/\\');
        $cacheFile = storage_path('/framework/cache/minify.php');
        $this->ensureCacheFileExists($cacheFile);

        $cache = require $cacheFile;
        $cachedFile = $cache[$file] ?? null;

        $realFilePath = base_path(rtrim($config['storage'], '/') . '/' . $file);
        $buildFilePath = $cachedFile;

        if (!file_exists($realFilePath)) {
            return abort(404);
        }

        $this->createNestedDirectories($config['buildStorage']);

        if ($this->shouldCache($realFilePath, $buildFilePath, $cachedFile)) {
            $content = file_get_contents($realFilePath);
            $mime = 'text/plain';
            if ($config['enabled']) {
                [$content, $mime] = $this->minifyContent($file, $content, $css, $js, $config);
            }

            if ($mime === 'text/css') {
                $ext = '.min.css';
            } elseif ($mime === 'application/javascript') {
                $ext = '.min.js';
            } else {
                $ext = '';
            }

            $cachedFile = Str::random('24') . $ext;
            file_put_contents($config['buildStorage'] . '/' . $cachedFile, $content);
            $cache[$file] = $config['buildStorage'] . '/' . $cachedFile;
            file_put_contents($cacheFile, $this->exportCache($cache));

        }else if ($this->shouldReMinify($realFilePath, $buildFilePath, $cachedFile)) {
            $content = file_get_contents($realFilePath);
            $mime = 'text/plain';

            if ($config['enabled']) {
                [$content, $mime] = $this->minifyContent($file, $content, $css, $js, $config);
            }

            if ($mime === 'text/css') {
                $ext = '.min.css';
            } elseif ($mime === 'application/javascript') {
                $ext = '.min.js';
            } else {
                $ext = '';
            }

            $newFileName = Str::random('24') . $ext;

            // remove the old file
            unlink($buildFilePath);

            $cachedFile = $config['buildStorage'] . '/' . $newFileName;
            file_put_contents($cachedFile, $content);
            $cache[$file] = $cachedFile;
            file_put_contents($cacheFile, $this->exportCache($cache));
        } else {
            // get the extension of the file
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $mime = 'text/plain';
            if ($ext === 'css') {
                $mime = 'text/css';
            } elseif ($ext === 'js') {
                $mime = 'application/javascript';
            }
            $content = file_get_contents($buildFilePath);
        }

        return response($content, 200, ['Content-Type' => $mime . '; charset=UTF-8']);
    }

    private function ensureCacheFileExists($cacheFile)
    {
        if (!file_exists($cacheFile)) {
            file_put_contents($cacheFile, "<?php\nreturn " . var_export([], true) . ";\n");
        }
    }

    private function createNestedDirectories($path)
    {
        $dirs = explode('/', $path);
        $currentPath = '';

        foreach ($dirs as $dir) {
            $currentPath .= $dir . '/';
            if (!is_dir(base_path($currentPath))) {
                mkdir(base_path($currentPath), 0755, true);
            }
        }
    }

    private function shouldCache($realFilePath, $buildFilePath, $cachedFile)
    {
        return file_exists($realFilePath) && (!file_exists($buildFilePath) || !$cachedFile);
    }

    private function shouldReMinify($realFilePath, $buildFilePath, $cachedFile)
    {
        return file_exists($realFilePath) && file_exists($buildFilePath) && $cachedFile
            && filemtime($realFilePath) > filemtime($buildFilePath);
    }

    private function minifyContent($file, $content, $css, $js, $config)
    {
        $mime = 'text/plain';

        if (preg_match("/\.css$/", $file)) {
            $content = $css->replace($content, $config['css_insert_semicolon']);
            $mime = 'text/css';
        } elseif (preg_match("/\.js$/", $file)) {
            $content = $js->replace($content, $config['js_insert_semicolon']);
            if ($config['obfuscate']) {
                $content = $js->obfuscate($content);
            }
            $mime = 'application/javascript';
        }

        return [$content, $mime];
    }

    private function exportCache($cache)
    {
        return "<?php\nreturn " . var_export($cache, true) . ";\n";
    }
}
