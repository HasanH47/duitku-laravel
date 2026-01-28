<?php

namespace Duitku\Laravel;

use Duitku\Laravel\Support\DuitkuConfig;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class DuitkuServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/duitku.php' => config_path('duitku.php'),
            ], 'duitku-config');
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'duitku');

        Blade::component('duitku-pop', \Duitku\Laravel\Components\Pop::class);
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/duitku.php', 'duitku'
        );

        $this->app->singleton(DuitkuConfig::class, function ($app) {
            return new DuitkuConfig($app['config']['duitku'] ?? []);
        });

        $this->app->singleton('duitku', function ($app) {
            return new Duitku($app[DuitkuConfig::class]);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return ['duitku', DuitkuConfig::class];
    }
}
