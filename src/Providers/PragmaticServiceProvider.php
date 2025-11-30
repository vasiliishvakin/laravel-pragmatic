<?php

declare(strict_types=1);

namespace Pragmatic\Providers;

use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;
use Pragmatic\Alerts\AlertManager;
use Pragmatic\Cache\Concerns\CacheMacros;
use Pragmatic\Cache\Concerns\CacheUtils;
use Pragmatic\Cqrs\CommandBus;
use Pragmatic\Cqrs\QueryBus;
use Pragmatic\Data\DataFactoryService;
use Pragmatic\Data\DataSerializerService;
use Pragmatic\Data\Mapping\MapperResolver;
use Pragmatic\Data\Mapping\MapperTransformer;
use Pragmatic\Debug\Commands\DebugDisableCommand;
use Pragmatic\Debug\Commands\DebugDriverCommand;
use Pragmatic\Debug\Commands\DebugEnableCommand;
use Pragmatic\Debug\Contracts\DebugManagerInstance;
use Pragmatic\Debug\DebugFactoryContainer;
use Pragmatic\Debug\DebugManager;
use Pragmatic\Hashing\FastHasher;
use Pragmatic\Json\JsonDriverContract;
use Pragmatic\Json\JsonFactoryContainer;
use Pragmatic\Json\JsonManager;
use Pragmatic\Json\JsonManagerInstance;
use Pragmatic\Support\DataAccessor;
use Pragmatic\Support\EnumHelperService;
use Pragmatic\Support\ReflectionReader;

final class PragmaticServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge main configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/pragmatic.php',
            'pragmatic'
        );

        // Merge debug configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/debug.php',
            'debug'
        );

        // JSON Manager
        $this->app->singleton(JsonFactoryContainer::class);
        $this->app->bind(JsonManagerInstance::class, JsonManager::class);
        $this->app->singleton(JsonManager::class);

        // Debug Manager
        $this->app->singleton(DebugFactoryContainer::class);
        $this->app->bind(DebugManagerInstance::class, DebugManager::class);
        $this->app->singleton(DebugManager::class);

        // Cache Utilities
        $this->app->singleton(CacheUtils::class, fn () => new CacheUtils(
            delimiter: config('pragmatic.cache.delimiter', ':'),
        ));

        // Alert Manager
        $this->app->singleton(AlertManager::class);

        // Data Utilities
        $this->app->singleton(DataAccessor::class);
        $this->app->singleton(ReflectionReader::class);
        $this->app->singleton(EnumHelperService::class);

        // Mapping Services
        $this->app->singleton(MapperTransformer::class);
        $this->app->singleton(MapperResolver::class);

        // DTO Services
        $this->app->singleton(DataFactoryService::class);
        $this->app->singleton(DataSerializerService::class);

        // CQRS Buses
        $this->app->singleton(QueryBus::class);
        $this->app->singleton(CommandBus::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register cache macros
        CacheMacros::register();

        // Add package info to about command
        AboutCommand::add('Laravel Pragmatic', fn () => [
            'Version' => '0.1.0',
            'CQRS' => 'Lightweight implementation',
        ]);

        if ($this->app->runningInConsole()) {
            // Publish configuration
            $this->publishes([
                __DIR__.'/../../config/pragmatic.php' => config_path('pragmatic.php'),
            ], 'pragmatic-config');

            $this->publishes([
                __DIR__.'/../../config/debug.php' => config_path('debug.php'),
            ], 'pragmatic-debug-config');

            // Load migrations
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

            // Register commands
            $this->commands([
                \Pragmatic\Console\Commands\PennantFlagCommand::class,
                DebugEnableCommand::class,
                DebugDisableCommand::class,
                DebugDriverCommand::class,
            ]);
        }

        // Register custom hash driver
        Hash::extend('fast', fn () => new FastHasher(
            algo: config('pragmatic.hash.algo', 'xxh3'),
        ));
    }
}
