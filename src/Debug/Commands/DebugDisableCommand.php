<?php

declare(strict_types=1);

namespace Pragmatic\Debug\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class DebugDisableCommand extends Command
{
    protected $signature = 'debug:disable
                            {--global : Disable globally by updating .env file}';

    protected $description = 'Disable debug mode for the current session or globally';

    public function handle(): int
    {
        if ($this->option('global')) {
            return $this->disableGlobally();
        }

        return $this->disableRuntime();
    }

    private function disableRuntime(): int
    {
        debug()->disable();

        $this->components->info('Debug mode disabled for current session.');
        $this->components->warn('This change is temporary and will reset on next request.');

        return self::SUCCESS;
    }

    private function disableGlobally(): int
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
                'DEBUG_ENABLED=false',
                $envContent
            );

            File::put($envPath, $newContent);

            $this->components->info('Debug mode disabled globally in .env file.');
            $this->components->warn('You may need to restart your application for changes to take effect.');
        } else {
            $this->components->warn('DEBUG_ENABLED not found in .env file. Debug is already disabled by default.');
        }

        return self::SUCCESS;
    }
}
