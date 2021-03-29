<?php
declare(strict_types=1);

namespace Chronhub\Projector\Console;

use Chronhub\Contracts\Messaging\DomainEvent;
use Chronhub\Contracts\Projecting\ProjectorFactory;
use Chronhub\Contracts\Projecting\ReadModel;
use Chronhub\Projector\Support\Facade\Project;
use Illuminate\Console\Command;

/**
 * @method string streamName()
 * @method ReadModel readModel()
 * @method void linkTo(string $streamName, DomainEvent $event)
 * @method void emit(DomainEvent $event)
 */
abstract class AbstractPersistentProjectionCommand extends Command
{
    public function withProjection(string $streamName,
                                   string|ReadModel $readModel = null,
                                   array $options = []): ProjectorFactory
    {
        if ($this->dispatchSignal()) {
            pcntl_async_signals(true);
        }

        $projection = $readModel
            ? Project::createReadModel($streamName, $readModel, $this->projectorName(), $options)
            : Project::createProjection($streamName, $this->projectorName(), $options);

        if ($this->dispatchSignal()) {
            pcntl_signal(SIGINT, function () use ($projection, $streamName): void {
                if (null !== $this->output) {
                    $this->warn("Stopping $streamName projection");
                }

                $projection->stop();
            });
        }

        return $projection;
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
