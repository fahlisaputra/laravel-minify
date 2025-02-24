<?php

/**
 * Minify the given file path.
 *
 * @param string $file The file path to minify.
 *
 * @throws Exception
 *
 * @return string The minified url.
 */
function minify(string $file): string
{
    if (!config('minify.assets_enabled')) {
        throw new \Exception('Minify assets is disabled');
    }

    $storage = config('minify.assets_storage', 'resources');
    $cacheFile = storage_path('/framework/cache/minify.php');

    if (!file_exists($cacheFile)) {
        file_put_contents($cacheFile, "<?php\nreturn " . var_export([], true) . ";\n");
    }

    // Normalize file path
    $file = ltrim($file, '/\\');

    $cache = require $cacheFile;
    $cachedFile = $cache[$file] ?? null;

    $realFilePath = base_path(rtrim($storage, '/') . '/' . $file);
    if (!file_exists($realFilePath)) {
        throw new \Exception("Cannot create minified route. File {$realFilePath} not found");
    }

    if ($cachedFile && file_exists($cachedFile)) {
        if (filemtime($realFilePath) > filemtime($cachedFile)) {
            $cachedFile = null;
        }
    }

    if ($cachedFile) {
        return asset(str_replace(public_path(), '', $cachedFile));
    }

    return route('minify.assets', ['file' => $file]);
}
