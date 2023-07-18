<?php

namespace Fahlisaputra\Minify\Controllers;

class HttpConnectionHandler {

    public function __invoke($file) {
        $path = resource_path($file);
        if (!file_exists($path)) {
            return abort(404);
        }

        $content = file_get_contents($path);
        $mime = mime_content_type($path);

        return response($content, 200, [
            "Content-Type" => $mime
        ]);
    }

}