<?php
declare(strict_types=1);

namespace Chronhub\Projector\Console;

use Chronhub\Contracts\Messaging\DomainEvent;
use Chronhub\Contracts\Projecting\Projector;
use Chronhub\Contracts\Projecting\ReadModel;
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
    protected ?Projector $projector = null;

    protected function withProjection(string $streamName,
                                      string|ReadModel $readModel = null,
                                      array $options = []): void
    {
        if ($this->dispatchSignal()) {
            pcntl_async_signals(true);
        }

        $this->projector = $readModel
            ? Project::createReadModel($streamName, $readModel, $this->projectorName(), $options)
            : Project::createProjection($streamName, $this->projectorName(), $options);
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

        return 'default';
    }

    protected function dispatchSignal(): bool
    {
        if ($this->hasOption('signal')) {
            return (int)$this->option('signal') === 1;
        }

        return false;
    }
}
