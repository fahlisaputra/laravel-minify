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

    // make sure the storage has trailing slash
    $file = base_path(rtrim($storage, '/').'/'.$file);

    if (!file_exists($file)) {
        throw new \Exception('Cannot create minified route. File '.$file.' not found');
    }

    // remove slash or backslash from the beginning of the file path
    $file = ltrim($file, '/\\');

    $path = route('minify.assets_route', ['file' => $file]);

    return $path;
}
