<?php

namespace Fahlisaputra\Minify;

function MinifyAsset($file) {
    $path = resource_path($file);
    if (!file_exists($path)) {
        throw new \Exception("File not found");
    }

    $path = '_minify/' . $file;

    return $path;
}