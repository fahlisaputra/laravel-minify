
# Laravel Minify

Laravel Minify is a package for minifying and obfuscating Javascript, CSS, HTML and Blade template files. It runs automatically when you load a page or view. This package can minify entire response and also can minify blade at compile time.

<p align="left">
<a href="https://packagist.org/packages/fahlisaputra/laravel-minify"><img src="http://poser.pugx.org/fahlisaputra/laravel-minify/v" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/fahlisaputra/laravel-minify"><img src="http://poser.pugx.org/fahlisaputra/laravel-minify/downloads" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/fahlisaputra/laravel-minify"><img src="http://poser.pugx.org/fahlisaputra/laravel-minify/license" alt="License"></a>
<a href="https://github.styleci.io/repos/667860309?branch=main"><img src="https://github.styleci.io/repos/667860309/shield?branch=main" alt="StyleCI"></a>
</p>

## Examples:

#### HTML
- Before Minify

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Laravel Minify</title>
    <style>
        h2 {
            color: red;
            background-color: yellow;
        }
    </style>
</head>
<body>
    <h2>Laravel Minify</h2>
    <p>Laravel Minify is a package for minifying and obfuscating Javascript, CSS, HTML and Blade template files.</p>
    <script>
        function helloWorld() {
            alert('Hello World');
        }
    </script>
</body>
</html>
```

- After Minify

```html
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta http-equiv="X-UA-Compatible" content="ie=edge"><title>Laravel Minify</title><style>h2{color:red;;background-color:yellow;}</style></head><body><h2>Laravel Minify</h2><p>Laravel Minify is a package for minifying and obfuscating Javascript, CSS, HTML and Blade template files.</p><script>eval(((_,__,___,____,_____,______,_______)=>{______[___](x=>_______[__](String[____](x)));return _______[_](_____)})('join','push','forEach','fromCharCode','',[102,117,110,99,116,105,111,110,32,104,101,108,108,111,87,111,114,108,100,40,41,123,97,108,101,114,116,40,39,72,101,108,108,111,32,87,111,114,108,100,39,41,125],[]));</script></body></html>
```

#### Javascript Obfuscate
- Before Obfuscate

```javascript
function helloWorld() {
    const number1 = 5;
    const number2 = 10;
    alert('Hello World! ' + (number1 + number2));
}
```

- After Obfuscate

```javascript
eval(((_,__,___,____,_____,______,_______)=>{______[___](x=>_______[__](String[____](x)));return _______[_](_____)})('join','push','forEach','fromCharCode','',[102,117,110,99,116,105,111,110,32,104,101,108,108,111,87,111,114,108,100,40,41,123,99,111,110,115,116,32,110,117,109,98,101,114,49,61,53,59,99,111,110,115,116,32,110,117,109,98,101,114,50,61,49,48,59,97,108,101,114,116,40,39,72,101,108,108,111,32,87,111,114,108,100,33,32,39,43,40,110,117,109,98,101,114,49,43,110,117,109,98,101,114,50,41,41,125],[]));
```


## Installation

Laravel Minify requires PHP 7.2 or higher. This particular version supports Laravel 6.x, 7.x, 8.x, 9.x, and 10.x only. 

To get the latest version, simply require the project using [Composer](https://getcomposer.org):

```sh
$ composer require fahlisaputra/laravel-minify
```
## Configuration
Laravel Minify supports optional configuration. To get started, you'll need to publish all vendor assets:

```sh
$ php artisan vendor:publish --provider="Fahlisaputra\Minify\MinifyServiceProvider"
```

This will create a config/minify.php file in your app that you can modify to set your configuration. Also, make sure you check for changes to the original config file in this package between releases.

## Register the Middleware
In order Laravel Minify can intercept your request to minify and obfuscate, you need to add the Minify middleware to the `app/Http/Kernel.php` file:

```php
protected $middleware = [
    ....
    // Middleware to minify html
    \Fahlisaputra\Minify\Middleware\MinifyCss::class,
    // Middleware to minify css
    \Fahlisaputra\Minify\Middleware\MinifyJavascript::class,
    // Middleware to minify javascript
    \Fahlisaputra\Minify\Middleware\MinifyHtml::class,
];
```
You can choose which middleware you want to use. Put all of them if you want to minify html, css, and javascript at the same time.

## Usage
This is how you can use Laravel Minify in your project. 
### Enable Minify
You can enable minify by setting `minify` to `true` in the `config/minify.php` file. For example:

```php
"enabled" => env("MINFY_ENABLED", true),
```

### Minify Asset Files
You must set `true` on `assets_enabled` in the `config/minify.php` file to minify your asset files. For example:

```php
"assets_enabled" => env("MINFY_ASSETS_ENABLED", true),
```

You can minify your asset files by using the `minify()` helper function. This function will minify your asset files and return the minify designed route. In order to work properly, you need to put your asset files in the `resources/js` or  `resources/css` directory. For example:

```html
<link rel="stylesheet" href="{{ minify('/css/test.css') }}">
```

where `test.css` is located in the `resources/css` directory.

```html
<script src="{{ minify('/js/test.js') }}"></script>
```

where `test.js` is located in the `resources/js` directory.

### Automatic Insert Semicolon on Javascript or CSS
You can enable automatic insert semicolon on javascript or css by setting `true` on `insert_semicolon` in the `config/minify.php` file. For example:

```php
"insert_semicolon" => [
    'css' => env("MINIFY_CSS_SEMICOLON", true),
    'js' => env("MINIFY_JS_SEMICOLON", true),
],
```
Please note: this feature is still experimental. It may not work properly and may cause errors to your javascript or css.

### Skip Minify on Blade
You can skip minify on blade by using attribute `ignore--minify` inside script or style tag. For example:

```html
<style ignore--minify>
    /* css */
</style>

<script ignore--minify>
   /* javascript */
</script>
```

### Skip Minify when Rendering View
You can skip minify when rendering view by passing `ignore_minify = true` in the view data. For example:

```php
return view('welcome', ['ignore_minify' => true]);
```

### Skip Minify by Route
You can skip minify by route by adding the route name to the `ignore` array in the `config/minify.php` file. For example:

```php
"ignore" => [
    '/admin'
],
```
## License
Laravel Minify is licensed under the [MIT license](LICENSE).

## Credits
- [Fahli Saputra](https://github.com/fahlisaputra)
- [:D](https://github.com/dz-id)
- [Spentura](https://spentura.com)

## Support
If you are having general issues with this package, feel free to contact us on [saputra@fahli.net](mailto:saputra@fahli.net)

## Report Vulnerability
Please read [our security policy](https://github.com/fahlisaputra/laravel-minify/security/policy) for more details.
