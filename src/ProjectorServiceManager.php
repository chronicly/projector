<?php
declare(strict_types=1);

namespace Chronhub\Projector;

use Chronhub\Contracts\Manager\ChroniclerManager;
use Chronhub\Contracts\Manager\ProjectorServiceManager as ServiceManager;
use Chronhub\Contracts\Messaging\MessageAlias;
use Chronhub\Contracts\Model\EventStreamProvider;
use Chronhub\Contracts\Model\ProjectionProvider;
use Chronhub\Contracts\Projecting\ProjectorManager as Manager;
use Chronhub\Contracts\Support\JsonEncoder;
use Chronhub\Projector\Exception\RuntimeException;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;

final class ProjectorServiceManager implements ServiceManager
{
    /**
     * @var array<string,callable>
     */
    protected array $customProjectors = [];
    protected array $projectors = [];
    protected array $config;

    public function __construct(protected Container $container)
    {
        $this->config = $container->get(Repository::class)->get('projector', []);
    }

    public function create(string $name = 'default'): Manager
    {
        if ($projector = $this->projectors[$name] ?? null) {
            return $projector;
        }

        $config = $this->fromProjector("projectors.$name");

        if (!is_array($config) || empty($config)) {
            throw new RuntimeException("Invalid configuration for projector manager $name");
        }

        return $this->projectors[$name] = $this->resolveProjectorManager($name, $config);
    }

    public function extend(string $name, callable $projectorManager): void
    {
        $this->customProjectors[$name] = $projectorManager;
    }

    private function resolveProjectorManager(string $name, array $config): ProjectorManager
    {
        if ($customProjector = $this->customProjectors[$name] ?? null) {
            return $customProjector($this->container, $config);
        }

        return $this->createDefaultProjectorManager($config);
    }

    private function createDefaultProjectorManager(array $config): ProjectorManager
    {
        return new ProjectorManager(
            $this->container->get(ChroniclerManager::class)->create($config['chronicler']),
            $this->determineEventStreamProvider($config),
            $this->determineProjectionProvider($config),
            $this->container->get(MessageAlias::class),
            $this->container->make($config['scope']),
            $this->container->get(JsonEncoder::class),
            $this->determineProjectorOptions($config['options'])
        );
    }

    protected function determineProjectorOptions(?string $optionKey): array
    {
        return $this->fromProjector("options.$optionKey") ?? [];
    }

    private function determineEventStreamProvider(array $config): EventStreamProvider
    {
        $eventStreamKey = $config['event_stream_provider'];

        $eventStream = $this->container[Repository::class]
            ->get("chronicler.provider.$eventStreamKey");

        return $this->container->make($eventStream);
    }

    private function determineProjectionProvider(array $config): ProjectionProvider
    {
        $projectionKey = $config['provider'];

        $projection = $this->fromProjector("provider.$projectionKey") ?? null;

        return $this->container->make($projection);
    }

    private function fromProjector(string $key): mixed
    {
        return Arr::get($this->config, $key);
    }
}
