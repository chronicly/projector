<?php
declare(strict_types=1);

namespace Chronhub\Projector\Console;

use Chronhub\Contracts\Messaging\DomainEvent;
use Chronhub\Contracts\Projecting\ProjectorFactory;
use Chronhub\Contracts\Projecting\ReadModel;
use Chronhub\Contracts\Query\ProjectionQueryFilter;
use Chronhub\Projector\Support\Facade\Project;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;

/**
 * @method string streamName()
 * @method ReadModel readModel()
 * @method void linkTo(string $streamName, DomainEvent $event)
 * @method void emit(DomainEvent $event)
 */
abstract class AbstractPersistentProjectionCommand extends Command implements SignalableCommandInterface
{
    protected const DEFAULT_PROJECTOR = 'default';
    protected const DISPATCH_SIGNAL = false;

    protected ?ProjectorFactory $projector = null;

    protected function withProjection(string $streamName,
                                      string|ReadModel $readModel = null,
                                      array $options = [],
                                      ?ProjectionQueryFilter $queryFilter = null): void
    {
        if ($this->dispatchSignal()) {
            pcntl_async_signals(true);
        }

        $this->projector = $readModel
            ? $this->projectReadModel($streamName, $readModel, $options, $queryFilter)
            : $this->projectPersistentProjection($streamName, $options, $queryFilter);
    }

    public function getSubscribedSignals(): array
    {
        return [SIGINT];
    }

    public function handleSignal(int $signal): void
    {
        if ($this->dispatchSignal()) {
            $this->line('Stopping projection ...');

            $this->projector->stop();
        }
    }

    protected function projectorName(): string
    {
        if ($this->hasOption('projector')) {
            return $this->option('projector');
        }

        return self::DEFAULT_PROJECTOR;
    }

    protected function dispatchSignal(): bool
    {
        if ($this->hasOption('signal')) {
            return (int)$this->option('signal') === 1;
        }

        return self::DISPATCH_SIGNAL;
    }

    private function projectReadModel(string $streamName,
                                      string|ReadModel $readModel,
                                      array $options = [],
                                      ?ProjectionQueryFilter $queryFilter = null): ProjectorFactory
    {
        if (is_string($readModel)) {
            $readModel = $this->getLaravel()->make($readModel);
        }

        $projector = Project::create($this->projectorName());

        $queryFilter = $queryFilter ?? $projector->queryScope()->fromIncludedPosition();

        return $projector
            ->createReadModelProjection($streamName, $readModel, $options)
            ->withQueryFilter($queryFilter);
    }

    private function projectPersistentProjection(string $streamName,
                                                 array $options = [],
                                                 ?ProjectionQueryFilter $queryFilter = null): ProjectorFactory
    {
        $projector = Project::create($this->projectorName());

        $queryFilter = $queryFilter ?? $projector->queryScope()->fromIncludedPosition();

        return $projector
            ->createProjection($streamName, $options)
            ->withQueryFilter($queryFilter);
    }
}
