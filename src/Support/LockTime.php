<?php
declare(strict_types=1);

namespace Chronhub\Projector\Support;

use DateTimeImmutable;
use DateTimeZone;

final class LockTime
{
    public const TIMEZONE = 'UTC';
    public const FORMAT = 'Y-m-d\TH:i:s.u';

    private DateTimeImmutable $dateTime;

    public static function fromNow(): self
    {
        $now = new DateTimeImmutable('now', new DateTimeZone(self::TIMEZONE));

        return new LockTime($now);
    }

    public function createLockUntil(int $lockTimeoutMs): string
    {
        $micros = (string)((int)$this->dateTime->format('u') + ($lockTimeoutMs * 1000));

        $secs = substr($micros, 0, -6);

        if ('' === $secs) {
            $secs = 0;
        }

        return $this->dateTime
                ->modify('+' . $secs . ' seconds')
                ->format('Y-m-d\TH:i:s') . '.' . substr($micros, -6);
    }

    public function toDateTime(): DateTimeImmutable
    {
        return $this->dateTime;
    }

    public function toString(): string
    {
        return $this->dateTime->format(self::FORMAT);
    }

    private function __construct(DateTimeImmutable $dateTime)
    {
        $this->dateTime = $dateTime;
    }
}
