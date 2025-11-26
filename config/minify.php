<?php

/*
| Minify for Laravel
|
| Copyright (c) 2023–2025 Contributors
| @see https://github.com/fahlisaputra/laravel-minify
|
| For the full copyright and license information,
| please view the LICENSE file that was distributed
| with this source code.
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

    'minify_blade' => env('MINIFY_BLADE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Minify Assets
    |--------------------------------------------------------------------------
    |
    | This option enables minification of the assets inside the resources/
    | directory. Only CSS and JS files will be minified. These optimizations
    | have little impact on php processing time.
    |
    | Place your assets in the `assets_path` option directory, and
    | they will be minified and served from the `assets_route` configured route.
    |
    | Default: true
    |
    */

    'minify_assets' => [

        /*
        | This option enables minification of the assets including CSS and JS files.
        */

        'enabled' => env('MINIFY_ASSETS_ENABLED', true),

        /*
        | This option specifies the storage path of the original assets to be minified.
        | This is relative to the base path of the application.
        */

        'assets_path' => env('MINIFY_ASSETS_PATH', 'resources'),

        /*
        | This option specifies the route to serve the minified assets.
        | Route will automatically be registered by the service provider.
        */

        'assets_route' =>  env('MINIFY_ASSETS_ROUTE', '_minify'),

        /*
        | Here you may configure the caching options for minified assets.
        | Cache improves performance by storing minified assets and serving
        | them from the cache directory if they have not been modified.
        */

        'cache' => [

            /*
            | This option enables caching of the minified assets
            */

            'enabled' => env('MINIFY_CACHE_ENABLED', true),

            /*
            | Specifies the storage path to save the minified assets.
            | Minify checks the timestamp of the assets to determine whether to
            | re-minify the assets. If the assets are not modified, Minify will serve
            | the minified assets from this directory, making it faster.
            |
            | Note: This directory will be created on the public path.
            */

            'cache_path' => env('MINIFY_CACHE_PATH', 'assets/_minify'),

        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Obfuscate JavaScript
    |--------------------------------------------------------------------------
    |
    | This option will obfuscate the JavaScript code. This may cause an error
    | if the code is not written properly. Please use with caution!
    |
    | Default: true
    |
    */

    'obfuscate_js' => env('MINIFY_OBFUSCATE_JS', true),

    /*
    |--------------------------------------------------------------------------
    | Automatic Insert Semicolon
    |--------------------------------------------------------------------------
    |
    | This option will automatically add semicolon at the end of the CSS and
    | JS code. This may cause an error if the code is not written properly.
    | Please use with caution!
    |
    | Default: false
    |
    */

    'auto_semicolon' => [

        /*
        | Automatically add semicolon at the end of the CSS code.
        */

        'css' => env('MINIFY_CSS_AUTO_SEMICOLON', false),

        /*
        | Automatically add semicolon at the end of the JS code.
        */

        'js'  => env('MINIFY_JS_AUTO_SEMICOLON', false),

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

    'remove_comments' => env('MINIFY_REMOVE_COMMENTS', true),

    /*
    |--------------------------------------------------------------------------
    | Skip LD+JSON Script Minification
    |--------------------------------------------------------------------------
    |
    | This option will skip minification of <script type="application/ld+json">
    | tags. LD+JSON scripts contain structured data that should remain readable
    | and unminified for SEO purposes.
    |
    | Default: true
    |
    */

    'skip_ld_json' => env('MINIFY_SKIP_LD_JSON', true),

    /*
    |--------------------------------------------------------------------------
    | Ignore Routes
    |--------------------------------------------------------------------------
    |
    | Here you can specify paths, which you don't want to minify. You can use
    | '*' as a wildcard.
    |
    */

    'ignore_routes' => [
        //   "*/download/*",
        //   "admin/*",
        //   "*/user"
    ],

    /*
    |--------------------------------------------------------------------------
    | Directive Replacement
    |--------------------------------------------------------------------------
    |
    | Here you can specify the directives that you want to replace. For example,
    | if you are using AlpineJS with shorthand directive @click, you can replace it
    | by adding '@' => 'x-on:' to the directive array.
    |
    */

    'directive_replacement' => [

        'enabled' => false,

        /*
        | Directives that you want to be replaced
        */
        'replaces' => [
            '@' => 'x-on:',
        ],

        /*
        | Directives that you don't want to be replaced
        */
        'keep' => [
            '@vite',
        ],

    ],
];
