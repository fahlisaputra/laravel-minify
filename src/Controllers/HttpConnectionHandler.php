<?php

namespace Fahlisaputra\Minify\Controllers;

use Fahlisaputra\Minify\Helpers\CSS;
use Fahlisaputra\Minify\Helpers\Javascript;

class HttpConnectionHandler
{
    public function __invoke($file)
    {
        $js_insert_semicolon = (bool) config('minify.insert_semicolon.js', true);
        $css_insert_semicolon = (bool) config('minify.insert_semicolon.css', true);
        $obfuscate = (bool) config('minify.obfuscate', false);
        $enabled = (bool) config('minify.assets_enabled', true);

        $css = new CSS();
        $js = new Javascript();

        $path = resource_path($file);
        if (!file_exists($path)) {
            return abort(404);
        }

        if (!preg_match("/^(css|js)\//", $file)) {
            return abort(404);
        }

        $content = file_get_contents($path);

        if ($enabled) {
            if (preg_match("/\.css$/", $file)) {
                $content = $css->replace($content, $css_insert_semicolon);
            } elseif (preg_match("/\.js$/", $file)) {
                $content = $js->replace($content, $js_insert_semicolon);
                if ($obfuscate) {
                    $content = $js->obfuscate($content);
                }
            }
        }

        $mime = mime_content_type($path);

        return response($content, 200, [
            'Content-Type' => $mime . '; charset=UTF-8',
        ]);
    }
}
