<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Unit\Pipe;

use Chronhub\Projector\Context\ProjectorContext;
use Chronhub\Projector\Pipe\PrepareQueryRunner;
use Chronhub\Projector\Tests\Double\HasTestingProjectorContext;
use Chronhub\Projector\Tests\TestCaseWithProphecy;
use ReflectionProperty;

final class PrepareQueryRunnerTest extends TestCaseWithProphecy
{
    use HasTestingProjectorContext;

    /**
     * @test
     */
    public function it_prepare_streams_positions(): void
    {
        $this->positions->watch(['names' => ['foo', 'bar']])->shouldBeCalled();

        $context = $this->newProjectorContext(false);
        $context->fromStreams('foo', 'bar');

        $pipe = new PrepareQueryRunner();

        $next = $pipe($context, function (ProjectorContext $context) {
            return fn() => $context;
        });

        $this->assertEquals($next, fn() => $context);
    }

    /**
     * @test
     */
    public function it_skip_watching_streams_if_it_as_already_been_prepared(): void
    {
        $this->positions->watch([])->shouldNotBeCalled();

        $context = $this->newProjectorContext(false);
        $pipe = new PrepareQueryRunner();

        $ref = new ReflectionProperty($pipe, 'isInitiated');
        $ref->setAccessible(true);
        $ref->setValue($pipe, true);

        $next = $pipe($context, function (ProjectorContext $context) {
            return fn() => $context;
        });

        $this->assertEquals($next, fn() => $context);
    }
}
