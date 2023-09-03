<?php

namespace Fahlisaputra\Minify;

use Fahlisaputra\Minify\Controllers\HttpConnectionHandler;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Fahlisaputra\Minify\Exceptions\InvalidMinifyException;

class MinifyServiceProvider extends BaseServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        $this->registerPublishables();
        $this->registerRoutes();
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->registerConfig();
    }

    protected function registerPublishables()
    {
        $this->publishes([
            __DIR__.'/../config/minify.php' => config_path('minify.php'),
        ], 'config');

        $this->createAssetDirectory();

    }

    public function registerConfig()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/minify.php', 'minify.php');
    }

    public function registerRoutes()
    {
        // get the route prefix from the config file
        $prefix = config('minify.route_prefix', '_minify');

        RouteFacade::get('/' . $prefix . '/{file?}', HttpConnectionHandler::class)
            ->where('file', '(.*)')
            ->name('minify.assets');
    }

    public function createAssetDirectory()
    {
        $path = base_path('/assets');
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $js = $path . '/js';
        if (!file_exists($js)) {
            mkdir($js, 0777, true);
        }

        file_put_contents($js . '/example.js', "console.log('Hello World!');");
    }
}
