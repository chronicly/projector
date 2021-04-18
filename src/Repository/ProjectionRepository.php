<?php
declare(strict_types=1);

namespace Chronhub\Projector\Repository;

use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Contracts\Chronicling\Chronicler;
use Chronhub\Contracts\Model\ProjectionProvider;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Contracts\Support\JsonEncoder;
use Chronhub\Foundation\Exception\StreamNotFound;
use Chronhub\Projector\Concern\HasProjectorRepository;

final class ProjectionRepository implements ProjectorRepository
{
    use HasProjectorRepository;

    public function __construct(protected ProjectorContext $context,
                                protected ProjectionProvider $provider,
                                protected TimeLock $timer,
                                protected JsonEncoder $jsonEncoder,
                                protected string $streamName,
                                private Chronicler $chronicler)
    {
    }

    public function initiate(): void
    {
        $this->context->runner()->stop(false);

        if (!$this->isProjectionExists()) {
            $this->createProjection();
        }

        $this->acquireLock();

        $this->context->position()->make($this->context->streamsNames());

        $this->loadState();
    }

    public function persist(): void
    {
        $this->persistProjection();
    }

    public function reset(): void
    {
        $this->resetProjection();

        $this->deleteStream();
    }

    public function delete(bool $withEmittedEvents): void
    {
        $this->deleteProjection();

        if ($withEmittedEvents) {
            $this->deleteStream();
        }
    }

    private function deleteStream(): void
    {
        try {
            $this->chronicler->delete(new StreamName($this->getStreamName()));
        } catch (StreamNotFound) {
            //
        }
    }
}
