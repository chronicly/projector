<?php
declare(strict_types=1);

namespace Chronhub\Projector\Factory;

use Chronhub\Contracts\Projecting\ProjectorRunner;

final class RunnerController implements ProjectorRunner
{
    public function __construct(private bool $runInBackground, private bool $isStopped)
    {
    }

    public function inBackground(): bool
    {
        return $this->runInBackground;
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
