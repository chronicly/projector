<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Unit\Pipe;

use Chronhub\Contracts\Projecting\EventCounter;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorOption;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Projector\Pipe\PersistOrUpdateLockBeforeResetCounter;
use Chronhub\Projector\Tests\TestCaseWithProphecy;

final class PersistOrSleepBeforeResetCounterTest extends TestCaseWithProphecy
{
    /**
     * @test
     */
    public function it_sleep_before_update_lock(): void
    {
        // checkMe missing usleep test
        $option = $this->prophesize(ProjectorOption::class);
        $option->sleep()->willReturn(1000)->shouldBeCalled();

        $eventCounter = $this->prophesize(EventCounter::class);
        $eventCounter->reset()->shouldBeCalled();
        $eventCounter->isReset()->willReturn(true)->shouldBeCalled();

        $context = $this->prophesize(ProjectorContext::class);
        $context->option()->willReturn($option->reveal());
        $context->counter()->willReturn($eventCounter->reveal());
        $context = $context->reveal();

        $repository = $this->prophesize(ProjectorRepository::class);
        $repository->updateLock()->shouldBeCalled();

        $pipe = new PersistOrUpdateLockBeforeResetCounter($repository->reveal());

        $expectedContext = $pipe($context, function (ProjectorContext $next) {
            return fn() => $next;
        });

        $this->assertEquals($expectedContext, fn() => $context);
    }

    /**
     * @test
     */
    public function it_persist_before_reset_counter(): void
    {
        $option = $this->prophesize(ProjectorOption::class);
        $option->sleep()->shouldNotBeCalled();

        $eventCounter = $this->prophesize(EventCounter::class);
        $eventCounter->reset()->shouldBeCalled();
        $eventCounter->isReset()->willReturn(false)->shouldBeCalled();

        $context = $this->prophesize(ProjectorContext::class);
        $context->counter()->willReturn($eventCounter->reveal());
        $context = $context->reveal();

        $repository = $this->prophesize(ProjectorRepository::class);
        $repository->persist()->shouldBeCalled();

        $pipe = new PersistOrUpdateLockBeforeResetCounter($repository->reveal());

        $next = $pipe($context, function (ProjectorContext $context) {
            return fn() => $context;
        });

        $this->assertEquals($next, fn() => $context);
    }
}
