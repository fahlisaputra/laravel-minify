<?php
/*
 * This file is part of Laravel Minify.
 *
 * (c) Fahli Saputra <saputra@fahli.net>
 * (c) DulLah <dulah755@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Minify Blade Views
    |--------------------------------------------------------------------------
    |
    | This option enables minification of the blade views as they are
    | compiled. These optimizations have little impact on php processing time
    | as the optimizations are only applied once and are cached. This package
    | will do nothing by default to allow it to be used without minifying
    | pages automatically.
    |
    | Default: true
    |
    */
    "enabled" => env("MINFY_ENABLED", true),

    /*
    |--------------------------------------------------------------------------
    | Automatic Insert Semicolon
    |--------------------------------------------------------------------------
    |
    | This option will automatically add semicolon at the end of the css and
    | js code. This may cause an error if the code is not written properly.
    | Please use with caution!
    |
    | Default: false
    |
    */
    "insert_semicolon" => [
        'css' => env("MINIFY_CSS_SEMICOLON", false),
        'js' => env("MINIFY_JS_SEMICOLON", false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Remove HTML Comments
    |--------------------------------------------------------------------------
    |
    | This option will remove all HTML comments from the output.
    |
    | Default: true
    |
    */
    "remove_comments" => env("MINIFY_REMOVE_COMMENTS", true),

    /*
    |--------------------------------------------------------------------------
    | Obsfucate Javascript
    |--------------------------------------------------------------------------
    |
    | This option will obsfucate the javascript code. This may cause an error
    | if the code is not written properly. Please use with caution!
    |
    | Default: false
    |
    */
    "obsfucate" => env("MINIFY_OBSFUCATE", false),

    /*
    |--------------------------------------------------------------------------
    | Ignore Routes
    |--------------------------------------------------------------------------
    |
    | Here you can specify paths, which you don't want to minify. You can use
    | '*' as wildcard.
    |
    */
 
    "ignore" => [
     //   "*/download/*",
     //   "admin/*",
     //   "*/user"
    ],
];
