<?php
declare(strict_types=1);

namespace Chronhub\Projector\Factory;

use Chronhub\Contracts\Clock\Clock;
use Chronhub\Contracts\Projecting\ProjectionState;

final class RunnerController
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

    public function till(ProjectionState $state, Clock $clock): bool
    {
        if(!$this->timeShifting){
           return false;
        }

        return ($this->timeShifting)($state, $clock);
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
