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
    if (config('minify.assets_enabled') === false) {
        throw new \Exception('Minify assets is disabled');
    }

    $storage = config('minify.assets_storage', 'resources');

    // remove slash or backslash from the beginning of the file path
    $file = ltrim($file, '/\\');

    // make sure the storage has trailing slash
    $realFilePath = rtrim($storage, '/').'/'.$file;
    if (!file_exists(base_path($realFilePath))) {
        throw new \Exception('Cannot create minified route. File '.$realFilePath.' not found');
    }

    return route('minify.assets', ['file' => $file]);
}
