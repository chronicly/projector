<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting;

use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Contracts\Chronicling\Chronicler;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Contracts\Projecting\ProjectorRepository as Repository;
use Chronhub\Contracts\Projecting\ReadModel;
use Chronhub\Foundation\Exception\StreamNotFound;
use Chronhub\Projector\Projecting\Concern\HasProjectorRepository;

final class ProjectionRepository implements ProjectorRepository
{
    use HasProjectorRepository;

    public function __construct(protected Repository $repository,
                                private Chronicler $chronicler)
    {
    }

    public function prepare(?ReadModel $readModel): void
    {
        $this->repository->prepare(null);
    }

    public function persist(): void
    {
        $this->repository->persist();
    }

    public function reset(): void
    {
        $this->repository->reset();

        $this->deleteStream();
    }

    public function delete(bool $withEmittedEvents): callable
    {
        $callback = $this->repository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->deleteStream();
        }

        return $callback;
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
