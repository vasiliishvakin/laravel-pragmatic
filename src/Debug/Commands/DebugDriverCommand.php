<?php

declare(strict_types=1);

namespace Pragmatic\Debug\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class DebugDriverCommand extends Command
{
    protected $signature = 'debug:driver
                            {driver? : The driver to set (core, laradumps, log)}
                            {--show : Show current driver}';

    protected $description = 'Set or show the default debug driver';

    public function handle(): int
    {
        if ($this->option('show')) {
            return $this->showCurrentDriver();
        }

        $driver = $this->argument('driver');

        if ($driver === null) {
            return $this->showAvailableDrivers();
        }

        return $this->setDriver($driver);
    }

    private function showCurrentDriver(): int
    {
        $currentDriver = config('debug.default');
        $isEnabled = config('debug.enabled');

        $this->components->info("Current debug driver: {$currentDriver}");
        $this->components->info('Debug mode: '.($isEnabled ? 'enabled' : 'disabled'));

        return self::SUCCESS;
    }

    private function showAvailableDrivers(): int
    {
        $drivers = array_keys(config('debug.drivers', []));
        $currentDriver = config('debug.default');

        $this->components->info('Available debug drivers:');

        foreach ($drivers as $driver) {
            $marker = $driver === $currentDriver ? ' (current)' : '';
            $this->line("  - {$driver}{$marker}");
        }

        $this->newLine();
        $this->components->info('Usage: php artisan debug:driver <driver>');

        return self::SUCCESS;
    }

    private function setDriver(string $driver): int
    {
        $availableDrivers = array_keys(config('debug.drivers', []));

        if (! in_array($driver, $availableDrivers, true)) {
            $this->components->error("Driver [{$driver}] is not available.");
            $this->line('Available drivers: '.implode(', ', $availableDrivers));

            return self::FAILURE;
        }

        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            $this->components->error('.env file not found.');

            return self::FAILURE;
        }

        $envContent = File::get($envPath);

        // Check if DEBUG_DRIVER already exists
        if (preg_match('/^DEBUG_DRIVER=/m', $envContent)) {
            // Update existing value
            $newContent = preg_replace(
                '/^DEBUG_DRIVER=.*/m',
                "DEBUG_DRIVER={$driver}",
                $envContent
            );
        } else {
            // Append new value
            $newContent = rtrim($envContent)."\n\nDEBUG_DRIVER={$driver}\n";
        }

        File::put($envPath, $newContent);

        $this->components->info("Default debug driver set to: {$driver}");
        $this->components->warn('You may need to restart your application for changes to take effect.');

        return self::SUCCESS;
    }
}
