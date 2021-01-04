<?php
declare(strict_types=1);

namespace Chronhub\Projector;

use Illuminate\Support\ServiceProvider;
use Chronhub\Contracts\Manager\ProjectorServiceManager as ServiceManager;

final class ProjectorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom($this->getConfigPath(), 'projector');

        if (empty($config = config('projector', []))) {
            return;
        }

        $this->app->singleton(ServiceManager::class, ProjectorServiceManager::class);
        $this->app->alias(ServiceManager::class, 'projector');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes(
                [$this->getConfigPath() => config_path('projector.php')],
                'config'
            );

            $console = config('projector.console') ?? [];

            if (true === $console['load_migrations'] ?? false) {
                $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
            }

            if (true === $console['load_commands'] ?? false) {
                $this->commands($console['commands']);
            }
        }
    }

    public function provides(): array
    {
        return [ServiceManager::class, 'projector'];
    }

    private function getConfigPath(): string
    {
        return __DIR__ . '/../config/projector.php';
    }
}
