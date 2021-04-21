<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Unit\Pipe;

use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\StreamPosition;
use Chronhub\Projector\Pipe\PrepareQueryRunner;
use Chronhub\Projector\Tests\TestCaseWithProphecy;
use ReflectionProperty;

final class PrepareQueryRunnerTest extends TestCaseWithProphecy
{
    /**
     * @test
     */
    public function it_prepare_streams_positions(): void
    {
        $streamPosition = $this->prophesize(StreamPosition::class);
        $context = $this->prophesize(ProjectorContext::class);

        $context->position()->willReturn($streamPosition);

        $context->streamsNames()->willReturn(['foo', 'bar']);

        $streamPosition->watch(['foo', 'bar'])->shouldBeCalled();

        $pipe = new PrepareQueryRunner();

        $next = $pipe($context->reveal(), function (ProjectorContext $context) {
            return fn() => $context;
        });

        $this->assertEquals($next, fn() => $context);
    }

    /**
     * @test
     */
    public function it_prepare_streams_positions_once(): void
    {
        $context = $this->prophesize(ProjectorContext::class);
        $context->position()->shouldNotBeCalled();

        $pipe = new PrepareQueryRunner();

        $ref = new ReflectionProperty($pipe, 'hasBeenPrepared');
        $ref->setAccessible(true);
        $ref->setValue($pipe, true);

        $next = $pipe($context->reveal(), function (ProjectorContext $context) {
            return fn() => $context;
        });

        $this->assertEquals($next, fn() => $context);
    }
}
