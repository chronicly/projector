<?php
declare(strict_types=1);

namespace Chronhub\Projector\Concern;

use Chronhub\Contracts\Chronicling\Chronicler;
use Chronhub\Contracts\Clock\Clock;
use Chronhub\Contracts\Model\EventStreamProvider;
use Chronhub\Contracts\Model\ProjectionProvider;
use Chronhub\Contracts\Projecting\ProjectorOption;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Contracts\Projecting\ReadModel;
use Chronhub\Contracts\Query\ProjectionQueryScope;
use Chronhub\Contracts\Support\JsonEncoder;
use Chronhub\Projector\Context\ProjectorContext;
use Chronhub\Projector\Exception\RuntimeException;
use Chronhub\Projector\Factory\EventCounter;
use Chronhub\Projector\Factory\StreamPosition;
use Chronhub\Projector\Repository\EventsProjectorRepository;
use Chronhub\Projector\Repository\ProjectionRepository;
use Chronhub\Projector\Repository\ReadModelRepository;
use Chronhub\Projector\Repository\TimeLock;
use Chronhub\Projector\Support\Option\ConstructableProjectorOption;
use Illuminate\Contracts\Events\Dispatcher;

trait HasConstructableProjectorManager
{
    public function __construct(protected Chronicler $chronicler,
                                protected EventStreamProvider $eventStreamProvider,
                                protected ProjectionProvider $projectionProvider,
                                protected ProjectionQueryScope $projectionQueryScope,
                                protected JsonEncoder $jsonEncoder,
                                protected Clock $clock,
                                protected ?Dispatcher $eventDispatcher = null,
                                protected ProjectorOption|array $options = [])
    {
    }

    protected function newProjectorRepository(ProjectorContext $context,
                                              string $streamName,
                                              ?ReadModel $readModel): ProjectorRepository
    {
        $repositoryClass = $readModel instanceof ReadModel
            ? ReadModelRepository::class : ProjectionRepository::class;

        $repository = new $repositoryClass(
            $context, $this->projectionProvider, $this->newTimeLock($context),
            $this->jsonEncoder, $streamName, $readModel ?? $this->chronicler
        );

        if ($this->eventDispatcher) {
            $repository = new EventsProjectorRepository($repository, $this->eventDispatcher);
        }

        return $repository;
    }

    protected function newProjectorContext(ProjectorOption $option,
                                           ?EventCounter $eventCounter): ProjectorContext
    {
        $streamPosition = new StreamPosition(
            $this->eventStreamProvider, $this->clock,
            $option->retriesMs(), $option->detectionWindows()
        );

        return new ProjectorContext($option, $streamPosition, $this->clock, $eventCounter);
    }

    protected function newProjectorOption(array $options): ProjectorOption
    {
        if (is_array($this->options)) {
            return new ConstructableProjectorOption(...array_merge($this->options, $options));
        }

        if (count($options) > 0) {
            throw new RuntimeException("Projector options can not be overridden");
        }

        return $this->options;
    }

    protected function newTimeLock(ProjectorContext $context): TimeLock
    {
        return new TimeLock(
            $this->clock,
            $context->option->lockTimoutMs(),
            $context->option->updateLockThreshold()
        );
    }
}
