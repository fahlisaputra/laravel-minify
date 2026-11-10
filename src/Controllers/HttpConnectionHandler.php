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

        // the {file?} route parameter is optional and may be null
        $file = (string) $file;

        // null bytes are never valid in a path (realpath() throws on PHP 8+)
        if (strpos($file, "\0") !== false) {
            return abort(404);
        }

        // remove slash or backslash from the beginning of the file path
        $file = ltrim($file, '/\\');

        // make sure the storage has trailing slash
        $file = base_path(rtrim($storage, '/').'/'.$file);

        // resolve "..", "." and symlinks, then only serve regular files that
        // are inside the storage directory (prevents path traversal)
        $storagePath = realpath(base_path(rtrim($storage, '/')));
        $file = realpath($file);

        if ($storagePath === false || $file === false || !is_file($file)
            || strpos($file, rtrim($storagePath, '/\\').DIRECTORY_SEPARATOR) !== 0) {
            return abort(404);
        }

        // only css and js assets are served (issue #9). Any other file in the
        // storage directory (blade views, lang files, logs, keys, ...) must not
        // be readable through the public assets route. The extension is taken
        // from the resolved path, so a symlink cannot disguise another file.
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mimes = [
            'css' => 'text/css',
            'js'  => 'application/javascript',
        ];

        if (!isset($mimes[$extension])) {
            return abort(404);
        }

        $content = file_get_contents($file);
        $mime = $mimes[$extension];

        if ($enabled) {
            if ($extension === 'css') {
                $content = $css->replace($content, $css_insert_semicolon);
            } else {
                $content = $js->replace($content, $js_insert_semicolon);
                if ($obfuscate) {
                    $content = $js->obfuscate($content);
                }
            }
        }

        return response($content, 200, [
            'Content-Type'           => $mime.'; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
