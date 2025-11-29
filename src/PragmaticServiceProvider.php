<?php

namespace Shvakin\Pragmatic;

use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;

class PragmaticServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/laravel-pragmatic.php',
            'laravel-pragmatic'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        AboutCommand::add('Laravel Pragmatic', fn () => ['Version' => '0.0.1']);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/laravel-pragmatic.php' => config_path('laravel-pragmatic.php'),
            ], 'laravel-pragmatic-config');
        }
    }
}
