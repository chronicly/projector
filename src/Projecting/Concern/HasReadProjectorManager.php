<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting\Concern;

use Chronhub\Contracts\Model\ProjectionModel;
use Chronhub\Contracts\Model\ProjectionProvider;
use Chronhub\Projector\Exception\ProjectionNotFound;

trait HasReadProjectorManager
{
    protected ProjectionProvider $projectionProvider;

    public function statusOf(string $projectionName): string
    {
        $projection = $this->projectionProvider->findByName($projectionName);

        if (!$projection instanceof ProjectionModel) {
            throw ProjectionNotFound::withName($projectionName);
        }

        return $projection->status();
    }

    public function streamPositionsOf(string $projectionName): array
    {
        $projection = $this->projectionProvider->findByName($projectionName);

        if (!$projection instanceof ProjectionModel) {
            throw ProjectionNotFound::withName($projectionName);
        }

        return $this->jsonEncoder->decode($projection->position());
    }

    public function stateOf(string $projectionName): array
    {
        $projection = $this->projectionProvider->findByName($projectionName);

        if (!$projection) {
            throw ProjectionNotFound::withName($projectionName);
        }

        return $this->jsonEncoder->decode($projection->state());
    }

    public function filterNamesOf(string ...$projectionNames): array
    {
        return $this->projectionProvider->findByNames(...$projectionNames);
    }

    public function projectionExists(string $projectionName): bool
    {
        return $this->projectionProvider->projectionExists($projectionName);
    }

    protected function assertProjectionNameExists(string $projectionName): void
    {
        if (!$this->projectionExists($projectionName)) {
            throw ProjectionNotFound::withName($projectionName);
        }
    }
}
