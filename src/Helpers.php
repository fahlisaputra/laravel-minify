<?php

/**
 * Minify the given file path.
 *
 * @param string $file The file path to minify.
 * @return string The minified url.
 * @throws Exception
 */
function minify(string $file) : string
{
    if (config('minify.assets_enabled') === false) {
        throw new \Exception('Minify assets is disabled');
    }

    $path = resource_path($file);
    if (!file_exists($path)) {
        throw new \Exception('File not found');
    }

    // remove slash or backslash from the beginning of the file path
    $file = ltrim($file, '/\\');

    $path = route('minify.assets_route', ['file' => $file]);

    return $path;
}
