<?php

use Fahlisaputra\Minify\Exceptions\InvalidMinifyException;

function minify($file)
{
    $path = base_path('/assets/'.$file);
    if (!file_exists($path)) {
        throw new InvalidMinifyException('File not found: '.$path);
    }

    // remove slash or backslash from the beginning of the file path
    $file = ltrim($file, '/\\');

    $prefix = config('minify.route_prefix', '_minify');
    $path = '/' . $prefix . '/'.$file;

    return $path;
}
