<?php

declare(strict_types=1);

namespace Volcanic\Commands;

use Illuminate\Console\Command;
use Volcanic\Volcanic;

class VolcanicCommand extends Command
{
    public $signature = 'volcanic {action=list : The action to perform (list, discover, routes)}';

    public $description = 'Manage Volcanic API endpoints';

    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'list' => $this->listApiModels(),
            'discover' => $this->discoverRoutes(),
            'routes' => $this->showRoutes(),
            default => $this->showHelp(),
        };
    }

    /**
     * List all models with the API attribute.
     */
    protected function listApiModels(): int
    {
        $volcanic = app(Volcanic::class);
        $apiModels = $volcanic->getApiModels();

        if (empty($apiModels)) {
            $this->info('No models found with the API attribute.');

            return self::SUCCESS;
        }

        $this->info('Models with API attribute:');
        $this->newLine();

        foreach ($apiModels as $modelClass => $apiConfig) {
            $this->line("• <fg=green>{$modelClass}</fg=green>");
            $this->line('  Operations: '.implode(', ', $apiConfig->getOperations()));
            $this->line("  Prefix: {$apiConfig->getPrefix()}");

            if ($apiConfig->getName()) {
                $this->line("  Resource: {$apiConfig->getName()}");
            }

            $this->newLine();
        }

        return self::SUCCESS;
    }

    /**
     * Manually discover and register routes.
     */
    protected function discoverRoutes(): int
    {
        $volcanic = app(Volcanic::class);

        $this->info('Discovering API routes...');
        $volcanic->discoverApiRoutes();
        $this->info('Routes discovered and registered successfully.');

        return self::SUCCESS;
    }

    /**
     * Show all registered routes.
     */
    protected function showRoutes(): int
    {
        $this->call('route:list', ['--name' => 'api']);

        return self::SUCCESS;
    }

    /**
     * Show help information.
     */
    protected function showHelp(): int
    {
        $this->info('Volcanic API Management Commands');
        $this->newLine();
        $this->line('<fg=yellow>Available actions:</fg=yellow>');
        $this->line('  <fg=green>list</fg=green>      List all models with the API attribute');
        $this->line('  <fg=green>discover</fg=green>  Manually discover and register API routes');
        $this->line('  <fg=green>routes</fg=green>    Show all registered API routes');
        $this->newLine();
        $this->line('<fg=yellow>Examples:</fg=yellow>');
        $this->line('  <fg=cyan>php artisan volcanic list</fg=cyan>');
        $this->line('  <fg=cyan>php artisan volcanic discover</fg=cyan>');
        $this->line('  <fg=cyan>php artisan volcanic routes</fg=cyan>');

        return self::SUCCESS;
    }
}
