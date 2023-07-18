
# Laravel Minify

Laravel Minify is a package for minifying and obfuscating Javascript, CSS, HTML and Blade template files. It runs automatically when you load a page or view. This package can minify entire response and also can minify blade at compile time.

<p align="center">
<a href="https://packagist.org/packages/fahlisaputra/laravel-minify"><img src="http://poser.pugx.org/fahlisaputra/laravel-minify/v" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/fahlisaputra/laravel-minify"><img src="http://poser.pugx.org/fahlisaputra/laravel-minify/downloads" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/fahlisaputra/laravel-minify"><img src="http://poser.pugx.org/fahlisaputra/laravel-minify/license" alt="License"></a>
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
</head>
<body>
    <h2>JavaScript Numbers</h2>
    <p>Number can be written with or without decimals.</p>
    <p id="demo"></p>
    <script src="{{ MinifyAsset('js/test.js') }}"></script>
</body>
</html>
```

- After Minify

```html
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta http-equiv="X-UA-Compatible" content="ie=edge"><title>Laravel Minify</title></head><body><h2>JavaScript Numbers</h2><p>Number can be written with or without decimals.</p><p id="demo"></p><script src="_minify/js/test.js"></script></body></html>
```

#### Javascript Obfuscate
- Before Obfuscate

```javascript
function myFunction() {
    var x = 999999999999999;
    var y = 9999999999999999;
    document.getElementById("demo").innerHTML = x + "<br>" + y;
}
```

- After Obfuscate

```javascript
eval(((_,__,___,____,_____,______,_______)=>{______[___](x=>_______[__](String[____](x)));return _______[_](_____)})('join','push','forEach','fromCharCode','',[102,117,110,99,116,105,111,110,32,109,121,70,117,110,99,116,105,111,110,40,41,123,118,97,114,32,120,61,57,57,57,57,57,57,57,57,57,57,57,57,57,57,57,59,118,97,114,32,121,61,57,57,57,57,57,57,57,57,57,57,57,57,57,57,57,57,59,100,111,99,117,109,101,110,116,46,103,101,116,69,108,101,109,101,110,116,66,121,73,100,40,34,100,101,109,111,34,41,46,105,110,110,101,114,72,84,77,76,61,120,43,34,60,98,114,62,34,43,121,125],[]));
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
$ php artisan vendor:publish --provider="Fahlisaputra\LaravelMinify\MinifyServiceProvider"
```

This will create a config/minify.php file in your app that you can modify to set your configuration. Also, make sure you check for changes to the original config file in this package between releases.


## License

[MIT](LICENSE) (MIT)