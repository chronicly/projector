<?php
declare(strict_types=1);

namespace Chronhub\Projector\Repository;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;

final class TimeLock
{
    public const TIMEZONE = 'UTC';
    public const FORMAT = 'Y-m-d\TH:i:s.u';

    private ?DateTimeImmutable $lastLockUpdate = null;

    public function __construct(private int $lockTimeoutMs, private int $lockThreshold)
    {
    }

    public function acquire(): array
    {
        $this->lastLockUpdate = $this->now();

        return [$this->current(), $this->lastLockUpdate->format(self::FORMAT)];
    }

    public function updateCurrentLock(): bool
    {
        $now = $this->now();

        if ($this->shouldUpdateLock($now)) {
            $this->lastLockUpdate = $now;

            return true;
        }

        return false;
    }

    public function refreshLockFromNow(): string
    {
        return $this->makeLock($this->now());
    }

    public function current(): string
    {
        return $this->makeLock($this->lastLockUpdate);
    }

    public function lastLockUpdate(): ?DateTimeImmutable
    {
        return $this->lastLockUpdate;
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone(self::TIMEZONE));
    }

    private function makeLock(DateTimeImmutable $dateTime): string
    {
        $micros = (string)((int)$dateTime->format('u') + ($this->lockTimeoutMs * 1000));

        $secs = substr($micros, 0, -6);

        if ('' === $secs) {
            $secs = 0;
        }

        return $dateTime
                ->modify('+' . $secs . ' seconds')
                ->format('Y-m-d\TH:i:s') . '.' . substr($micros, -6);
    }

    private function shouldUpdateLock(DateTimeImmutable $datetime): bool
    {
        if (null === $this->lastLockUpdate || 0 === $this->lockThreshold) {
            return true;
        }

        $updateLockThreshold = new DateInterval(sprintf('PT%sS', floor($this->lockThreshold / 1000)));

        $updateLockThreshold->f = ($this->lockThreshold % 1000) / 1000;

        $updatedLock = $this->lastLockUpdate->add($updateLockThreshold);

        return $updatedLock <= $datetime;
    }
}
