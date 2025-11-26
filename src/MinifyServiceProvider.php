<?php

namespace Fahlisaputra\Minify;

use Fahlisaputra\Minify\Controllers\HttpConnectionHandler;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for Laravel Minify package.
 *
 * Handles configuration, publishing, and route registration for
 * minifying assets (CSS/JS) and HTML output.
 */
class MinifyServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap any application services.
     *
     * Publishes configuration files and registers asset routes.
     */
    public function boot(): void
    {
        $this->registerPublishables();
        $this->registerRoutes();
    }

    /**
     * Register the application services.
     *
     * Merges package configuration with application's config.
     */
    public function register(): void
    {
        $this->registerConfig();
    }

    /**
     * Register the configuration publishable for the package.
     *
     * This allows users to publish the config file to their
     * application's config directory.
     */
    protected function registerPublishables(): void
    {
        $this->publishes([
            __DIR__.'/../config/minify.php' => config_path('minify.php'),
        ], 'config');
    }

    /**
     * Merge package configuration with application configuration.
     *
     * Supports both new and legacy configuration keys.
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/minify.php',
            'minify'
        );
    }

    /**
     * Register routes for serving minified assets.
     *
     * Only registers routes if minify assets is enabled in config.
     * Supports backward compatible config keys.
     */
    protected function registerRoutes(): void
    {
        $assetsEnabled = config('minify.assets_enabled', config('minify.minify_assets.enabled', true));
        if (!$assetsEnabled) {
            return;
        }

        $assetsRoute = config('minify.assets_route', config('minify.minify_assets.assets_route', '_minify'));
        Route::get('/'.$assetsRoute.'/{file?}', HttpConnectionHandler::class)
            ->where('file', '(.*)')
            ->name('minify.assets');
    }
}
