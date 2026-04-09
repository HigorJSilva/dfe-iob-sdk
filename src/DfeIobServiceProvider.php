<?php

namespace Emitte\DfeIob;

use Emitte\DfeIob\Auth\LaravelTokenStore;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\ServiceProvider;

class DfeIobServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/dfe-iob.php', 'dfe-iob');

        $this->app->singleton(DfeIobSdk::class, function ($app) {
            $config = $app['config']->get('dfe-iob', []);

            $cacheStoreName = $config['token_cache']['store'] ?? null;

            /** @var CacheRepository $cache */
            $cache = $cacheStoreName
                ? $app['cache']->store($cacheStoreName)
                : $app['cache']->store();

            $tokenStore = new LaravelTokenStore($cache);

            return DfeIobSdk::make($config, $tokenStore);
        });

        $this->app->alias(DfeIobSdk::class, 'dfe-iob');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/dfe-iob.php' => config_path('dfe-iob.php'),
            ], 'dfe-iob-config');
        }
    }
}
