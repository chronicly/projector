<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Double;

use Chronhub\Contracts\Clock\Clock;
use Chronhub\Contracts\Projecting\EventCounter;
use Chronhub\Contracts\Projecting\ProjectorOption;
use Chronhub\Contracts\Projecting\StreamPosition;
use Chronhub\Projector\Context\ProjectorContext;
use Prophecy\Prophecy\ObjectProphecy;

trait HasTestingProjectorContext
{
    protected ProjectorOption|ObjectProphecy $options;
    protected StreamPosition|ObjectProphecy $positions;
    protected Clock|ObjectProphecy $clock;
    protected null|EventCounter|ObjectProphecy $counter = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->options = $this->prophesize(ProjectorOption::class);
        $this->positions = $this->prophesize(StreamPosition::class);
        $this->clock = $this->prophesize(Clock::class);
        $this->counter = $this->prophesize(EventCounter::class);
    }

    protected function newProjectorContext(bool $withCounter): ProjectorContext
    {
        return new ProjectorContext(
            $this->options->reveal(),
            $this->positions->reveal(),
            $this->clock->reveal(),
           $withCounter ? $this->counter->reveal() : null,
        );
    }
}
