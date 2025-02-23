<?php

namespace Fahlisaputra\Minify;

use Fahlisaputra\Minify\Controllers\HttpConnectionHandler;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

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
    }

    public function registerConfig()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/minify.php',
            'minify'
        );
    }

    public function registerRoutes()
    {
        if (!config('minify.assets_enabled', true)) {
            return;
        }

        $path = config('minify.assets_route', '_minify');
        RouteFacade::get('/'.$path.'/{file?}', HttpConnectionHandler::class)
            ->where('file', '(.*)')
            ->name('minify.assets');
    }
}
