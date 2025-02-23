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

        $storage = config('minify.assets_storage', 'resources');

        // remove slash or backslash from the beginning of the file path
        $file = ltrim($file, '/\\');

        // make sure the storage has trailing slash
        $file = base_path(rtrim($storage, '/').'/'.$file);

        if (!file_exists($file)) {
            return abort(404);
        }

        $content = file_get_contents($file);
        $mime = 'text/plain';

        // due to support only for css and js (issue #9)
        if ($enabled) {
            if (preg_match("/\.css$/", $file)) {
                $content = $css->replace($content, $css_insert_semicolon);
                $mime = 'text/css';
            } elseif (preg_match("/\.js$/", $file)) {
                $content = $js->replace($content, $js_insert_semicolon);
                if ($obfuscate) {
                    $content = $js->obfuscate($content);
                }
                $mime = 'application/javascript';
            }
        }

        return response($content, 200, [
            'Content-Type' => $mime.'; charset=UTF-8',
        ]);
    }
}
