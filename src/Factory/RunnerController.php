<?php
declare(strict_types=1);

namespace Chronhub\Projector\Factory;

use Chronhub\Contracts\Clock\Clock;
use Chronhub\Contracts\Projecting\ProjectorRunner;

final class RunnerController implements ProjectorRunner
{
    /**
     * @var callable|null
     */
    private $timeShifting;

    public function __construct(private bool $runInBackground, private bool $isStopped, ?callable $timeShifting)
    {
        $this->timeShifting = $timeShifting;
    }

    public function inBackground(): bool
    {
        return $this->runInBackground;
    }

    public function keepTill(array $state, Clock $now): bool
    {
        if (!$this->timeShifting) {
            return false;
        }

        return true === ($this->timeShifting)($state, $now->pointInTime());
    }

    public function isStopped(): bool
    {
        return $this->isStopped;
    }

    public function stop(bool $isStopped): void
    {
        $this->isStopped = $isStopped;
    }
}
