<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Unit\Pipe;

use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Projector\Context\ProjectorContext;
use Chronhub\Projector\Pipe\PersistOrUpdateLock;
use Chronhub\Projector\Tests\Double\HasTestingProjectorContext;
use Chronhub\Projector\Tests\TestCaseWithProphecy;
use Prophecy\Prophecy\ObjectProphecy;

final class PersistOrUpdateLockTest extends TestCaseWithProphecy
{
    use HasTestingProjectorContext;

    /**
     * @test
     */
    public function it_sleep_before_update_lock(): void
    {
        $this->positions->hasGap()->willReturn(false)->shouldBeCalled();
        $this->options->sleep()->willReturn(1000)->shouldBeCalled();
        $this->counter->isReset()->willReturn(true)->shouldBeCalled();

        $repository = $this->prophesize(ProjectorRepository::class);
        $repository->updateLock()->shouldBeCalled();

        $this->sendContext($repository);
    }

    /**
     * @test
     */
    public function it_persist(): void
    {
        $this->positions->hasGap()->willReturn(false)->shouldBeCalled();
        $this->options->sleep()->shouldNotBeCalled();
        $this->counter->isReset()->willReturn(false)->shouldBeCalled();

        $repository = $this->prophesize(ProjectorRepository::class);
        $repository->persist()->shouldBeCalled();

        $this->sendContext($repository);
    }

    /**
     * @test
     */
    public function it_keep_workflow_if_gap_detected(): void
    {
        $this->positions->hasGap()->willReturn(true)->shouldBeCalled();
        $this->options->sleep()->shouldNotBeCalled();
        $this->counter->isReset()->shouldNotBeCalled();

        $repository = $this->prophesize(ProjectorRepository::class);
        $repository->persist()->shouldNotBeCalled();
        $repository->updateLock()->shouldNotBeCalled();

        $this->sendContext($repository);
    }

    private function sendContext(ObjectProphecy|ProjectorRepository $repository): void
    {
        $context = $this->newProjectorContext(true);

        $pipe = new PersistOrUpdateLock($repository->reveal());

        $expectedContext = $pipe($context, function (ProjectorContext $next) {
            return fn() => $next;
        });

        $this->assertEquals($expectedContext, fn() => $context);
    }
}
