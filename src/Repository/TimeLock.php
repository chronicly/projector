<?php
declare(strict_types=1);

namespace Chronhub\Projector\Repository;

use Chronhub\Contracts\Clock\Clock;
use Chronhub\Contracts\Clock\PointInTime;
use DateInterval;
use DateTimeImmutable;

final class TimeLock
{
    private ?PointInTime $lastLockUpdate = null;

    public function __construct(private Clock $clock,
                                private int $lockTimeoutMs,
                                private int $lockThreshold)
    {
    }

    public function acquire(): string
    {
        $this->lastLockUpdate = $this->now();

        return $this->current();
    }

    public function update(): bool
    {
        $now = $this->now();

        if ($this->shouldUpdateLock($now)) {
            $this->lastLockUpdate = $now;

            return true;
        }

        return false;
    }

    public function refresh(): string
    {
        return $this->createLockWithMillisecond($this->now());
    }

    public function current(): string
    {
        return $this->createLockWithMillisecond($this->lastLockUpdate);
    }

    public function lastLockUpdate(): ?PointInTime
    {
        return $this->lastLockUpdate;
    }

    private function createLockWithMillisecond(PointInTime $pointInTime): string
    {
        $dateTime = $pointInTime->dateTime();

        $microSeconds = (string)((int)$dateTime->format('u') + ($this->lockTimeoutMs * 1000));

        $seconds = substr($microSeconds, 0, -6);

        if ('' === $seconds) {
            $seconds = 0;
        }

        return $dateTime
                ->modify('+' . $seconds . ' seconds')
                ->format('Y-m-d\TH:i:s') . '.' . substr($microSeconds, -6);
    }

    private function shouldUpdateLock(PointInTime $pointInTime): bool
    {
        if (null === $this->lastLockUpdate || 0 === $this->lockThreshold) {
            return true;
        }

        return $this->incrementLockWithThreshold() <= $pointInTime->dateTime();
    }

    private function incrementLockWithThreshold(): DateTimeImmutable
    {
        $interval = sprintf('PT%sS', floor($this->lockThreshold / 1000));

        $updateLockThreshold = new DateInterval($interval);

        $updateLockThreshold->f = ($this->lockThreshold % 1000) / 1000;

        return $this->lastLockUpdate->dateTime()->add($updateLockThreshold);
    }

    private function now(): PointInTime
    {
        return $this->clock->pointInTime();
    }
}
