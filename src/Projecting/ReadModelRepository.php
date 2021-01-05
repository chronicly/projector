<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting;

use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Contracts\Projecting\ReadModel;
use Chronhub\Projector\Projecting\Concern\HasProjectorRepository;

final class ReadModelRepository implements ProjectorRepository
{
    use HasProjectorRepository;

    public function __construct(protected ProjectorRepository $repository,
                                private ReadModel $readModel)
    {
    }

    public function prepare(?ReadModel $readModel): void
    {
        $this->repository->prepare($readModel ?? $this->readModel);
    }

    public function persist(): void
    {
        $this->repository->persist();

        $this->readModel->persist();
    }

    public function reset(): void
    {
        $this->repository->reset();

        $this->readModel->reset();
    }

    public function delete(bool $withEmittedEvents): callable
    {
        $callback = $this->repository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->readModel->down();
        }

        return $callback;
    }
}
