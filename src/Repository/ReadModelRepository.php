<?php
declare(strict_types=1);

namespace Chronhub\Projector\Repository;

use Chronhub\Contracts\Model\ProjectionProvider;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Contracts\Projecting\ReadModel;
use Chronhub\Contracts\Support\JsonEncoder;
use Chronhub\Projector\Concern\HasProjectorRepository;

final class ReadModelRepository implements ProjectorRepository
{
    use HasProjectorRepository;

    public function __construct(protected ProjectorContext $context,
                                protected ProjectionProvider $provider,
                                protected TimeLock $timer,
                                protected JsonEncoder $jsonEncoder,
                                protected string $streamName,
                                private ReadModel $readModel)
    {
    }

    public function initiate(): void
    {
        $this->context->runner()->stop(false);

        if (!$this->isProjectionExists()) {
            $this->createProjection();
        }

        $this->acquireLock();

        if (!$this->readModel->isInitialized()) {
            $this->readModel->initialize();
        }

        $this->context->position()->watch($this->context->streamsNames());

        $this->loadState();
    }

    public function persist(): void
    {
        $this->persistProjection();

        $this->readModel->persist();
    }

    public function reset(): void
    {
        $this->resetProjection();

        $this->readModel->reset();
    }

    public function delete(bool $withEmittedEvents): void
    {
        $this->deleteProjection();

        if ($withEmittedEvents) {
            $this->readModel->down();
        }
    }
}
