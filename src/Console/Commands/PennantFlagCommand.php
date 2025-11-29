<?php

declare(strict_types=1);

namespace Pragmatic\Console\Commands;

use Illuminate\Console\Command;

final class PennantFlagCommand extends Command
{
    protected $signature = 'toolbox:pennant
        {flag : Feature class (e.g. EnableRegistration) or string name}
        {action? : activate|deactivate|status (default: status)}
        {--scope= : Scope for the flag (user id, etc). Default: null (global)}';

    protected $description = 'Activate, deactivate, or show status of a Laravel Pennant flag (globally or by scope)';

    public function handle(): int
    {
        if (! function_exists('feature')) {
            $this->error('Laravel Pennant is not installed. Install it with: composer require laravel/pennant');

            return self::FAILURE;
        }

        $flag = $this->argument('flag');
        $action = $this->argument('action') ?? 'status';
        $scope = $this->option('scope');

        $flag = ltrim($flag, '\\/');

        if (
            preg_match('/^[A-Z][A-Za-z0-9_]+$/', $flag) &&
            ! str_starts_with($flag, 'App\\Features\\')
        ) {
            $flag = 'App\\Features\\'.$flag;
        }

        if ($scope === null || strtolower($scope) === 'null') {
            $scope = null;
        }

        match ($action) {
            'activate' => $this->activate($flag, $scope),
            'deactivate' => $this->deactivate($flag, $scope),
            'status' => $this->status($flag, $scope),
            default => $this->status($flag, $scope),
        };

        return self::SUCCESS;
    }

    private function activate(string $flag, mixed $scope): void
    {
        feature()->for($scope)->activate($flag);
        $scopeText = $scope === null ? 'globally' : "for scope [{$scope}]";
        $this->info("Feature [{$flag}] activated {$scopeText}.");
    }

    private function deactivate(string $flag, mixed $scope): void
    {
        feature()->for($scope)->deactivate($flag);
        $scopeText = $scope === null ? 'globally' : "for scope [{$scope}]";
        $this->info("Feature [{$flag}] deactivated {$scopeText}.");
    }

    private function status(string $flag, mixed $scope): void
    {
        $active = feature()->for($scope)->active($flag);
        $statusText = $active ? '<fg=green>ACTIVE</>' : '<fg=red>INACTIVE</>';
        $scopeText = $scope === null ? 'globally' : "for scope [{$scope}]";
        $this->line("Feature [{$flag}] is {$statusText} {$scopeText}.");
    }
}
