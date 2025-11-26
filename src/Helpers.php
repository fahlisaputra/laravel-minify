<?php

use Illuminate\Support\Facades\URL;

/**
 * Get the minified URL for a given asset file.
 *
 * This helper resolves the minified version of a CSS or JS file
 * based on the minify configuration. It supports caching to
 * improve performance and ensures backward compatibility with
 * older configuration keys.
 *
 * @param string $file Relative file path from the assets directory
 *
 * @throws \Exception If the minify assets feature is disabled or the file does not exist
 *
 * @return string URL to the minified asset
 */
function minify(string $file): string
{
    // Check if minify assets feature is enabled
    $assetsEnabled = config('minify.minify_assets.enabled', config('minify.assets_enabled', true));
    if (!$assetsEnabled) {
        throw new \Exception('Minify assets is disabled in configuration.');
    }

    // Determine storage path (backward compatible)
    $storage = config('minify.minify_assets.assets_path', config('minify.assets_storage', 'resources'));

    $isCacheEnabled = config('minify.cache.enabled', false);
    if (!$isCacheEnabled) {
        // Directly return minify route if cache is disabled
        return route('minify.assets', ['file' => $file]);
    }

    // Ensure cache file exists
    $cacheFile = storage_path('framework/cache/minify.php');
    if (!file_exists($cacheFile)) {
        file_put_contents($cacheFile, "<?php\nreturn ".var_export([], true).";\n");
    }

    // Normalize file path
    $file = ltrim(str_replace(['\\', '/'], '/', $file), '/');

    $cache = require $cacheFile;
    $cachedFile = $cache[$file] ?? null;

    $realFilePath = base_path(rtrim($storage, '/').'/'.$file);
    if (!file_exists($realFilePath)) {
        throw new \Exception("Cannot create minified route. File '{$realFilePath}' not found.");
    }

    // Check cache timestamp
    if ($cachedFile && file_exists($cachedFile)) {
        if (filemtime($realFilePath) > filemtime($cachedFile)) {
            $cachedFile = null; // force regenerate
        }
    }

    // Return cached asset URL if available
    if ($cachedFile) {
        return asset(str_replace(public_path(), '', $cachedFile));
    }

    // Fallback to minify route
    return route('minify.assets', ['file' => $file]);
}
