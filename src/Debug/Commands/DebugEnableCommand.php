<?php

declare(strict_types=1);

namespace Pragmatic\Debug\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class DebugEnableCommand extends Command
{
    protected $signature = 'debug:enable
                            {--global : Enable globally by updating .env file}';

    protected $description = 'Enable debug mode for the current session or globally';

    public function handle(): int
    {
        if ($this->option('global')) {
            return $this->enableGlobally();
        }

        return $this->enableRuntime();
    }

    private function enableRuntime(): int
    {
        debug()->enable();

        $this->components->info('Debug mode enabled for current session.');
        $this->components->warn('This change is temporary and will reset on next request.');

        return self::SUCCESS;
    }

    private function enableGlobally(): int
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            $this->components->error('.env file not found.');

            return self::FAILURE;
        }

        $envContent = File::get($envPath);

        // Check if DEBUG_ENABLED already exists
        if (preg_match('/^DEBUG_ENABLED=/m', $envContent)) {
            // Update existing value
            $newContent = preg_replace(
                '/^DEBUG_ENABLED=.*/m',
                'DEBUG_ENABLED=true',
                $envContent
            );
        } else {
            // Append new value
            $newContent = rtrim($envContent)."\n\nDEBUG_ENABLED=true\n";
        }

        File::put($envPath, $newContent);

        $this->components->info('Debug mode enabled globally in .env file.');
        $this->components->warn('You may need to restart your application for changes to take effect.');

        return self::SUCCESS;
    }
}
