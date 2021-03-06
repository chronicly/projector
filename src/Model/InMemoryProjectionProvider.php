<?php
declare(strict_types=1);

namespace Chronhub\Projector\Model;

use Chronhub\Contracts\Clock\Clock;
use Chronhub\Contracts\Model\ProjectionModel;
use Chronhub\Contracts\Model\ProjectionProvider;
use Illuminate\Support\Collection;

final class InMemoryProjectionProvider implements ProjectionProvider
{
    /**
     * @var Collection<InMemoryProjection>
     */
    private Collection $projections;

    public function __construct(private Clock $clock)
    {
        $this->projections = new Collection();
    }

    public function createProjection(string $name, string $status): bool
    {
        if ($this->projectionExists($name)) {
            return false;
        }

        $projection = InMemoryProjection::create($name, $status);

        $this->projections->put($name, $projection);

        return true;
    }

    public function updateProjection(string $name, array $data): bool
    {
        /** @var InMemoryProjection $projection */
        if (null === $projection = $this->findByName($name)) {
            return false;
        }

        $projection->setState($data['state'] ?? null);
        $projection->setPosition($data['position'] ?? null);
        $projection->setStatus($data['status'] ?? null);
        $projection->setLockedUntil($data['locked_until'] ?? null);

        return true;
    }

    public function deleteByName(string $name): bool
    {
        if (!$this->projections->has($name)) {
            return false;
        }

        unset($this->projections[$name]);

        return true;
    }

    public function projectionExists(string $name): bool
    {
        return $this->projections->has($name);
    }

    public function findByName(string $name): ?ProjectionModel
    {
        return $this->projections->get($name);
    }

    public function findByNames(string ...$names): array
    {
        $found = array();

        foreach ($names as $name) {
            if ($this->findByName($name)) {
                $found[] = $name;
            }
        }

        return $found;
    }

    public function acquireLock(string $name, string $status, string $lockedUntil, string $now): bool
    {
        if (null === $projection = $this->findByName($name)) {
            return false;
        }

        /** @var InMemoryProjection $projection */
        if ($this->shouldUpdateLock($projection, $now)) {
            $projection->setStatus($status);
            $projection->setLockedUntil($lockedUntil);

            return true;
        }

        return false;
    }

    private function shouldUpdateLock(ProjectionModel $model, string $now): bool
    {
        if (null === $model->lockedUntil()) {
            return true;
        }

        return $this->clock->fromString($now)->after($this->clock->fromString($model->lockedUntil()));
    }
}
