<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Unit\Context;

use Chronhub\Contracts\Query\ProjectionQueryFilter;
use Chronhub\Projector\Factory\InMemoryState;
use Chronhub\Projector\Factory\ProjectionStatus;
use Chronhub\Projector\Factory\RunnerController;
use Chronhub\Projector\Support\Timer\NullTimer;
use Chronhub\Projector\Support\Timer\ProcessTimer;
use Chronhub\Projector\Tests\Double\HasTestingProjectorContext;
use Chronhub\Projector\Tests\TestCaseWithProphecy;

final class ProjectorContextTest extends TestCaseWithProphecy
{
    use HasTestingProjectorContext;

    /**
     * @test
     */
    public function it_can_be_instantiated(): void
    {
        $context = $this->newProjectorContext(true);

        $this->assertEquals($this->options->reveal(), $context->option);
        $this->assertEquals($this->positions->reveal(), $context->position);
        $this->assertEquals($this->clock->reveal(), $context->clock);
        $this->assertEquals($this->counter->reveal(), $context->eventCounter);

        $this->assertEquals(ProjectionStatus::IDLE(), $context->status);
        $this->assertInstanceOf(InMemoryState::class, $context->state);
        $this->assertInstanceOf(RunnerController::class, $context->runner());
    }

    /**
     * @test
     */
    public function it_set_projection_query_filter(): void
    {
        $queryFilter = $this->prophesize(ProjectionQueryFilter::class)->reveal();

        $context = $this->newProjectorContext(false);

        $context->withQueryFilter($queryFilter);

        $this->assertEquals($queryFilter, $context->queryFilter());
    }

    /**
     * @test
     */
    public function it_set_streams_names(): void
    {
        $context = $this->newProjectorContext(false);

        $context->fromStreams('foo', 'bar');

        $this->assertEquals(['names' => ['foo', 'bar']], $context->streamsNames());
    }

    /**
     * @test
     */
    public function it_set_categories(): void
    {
        $context = $this->newProjectorContext(false);

        $context->fromCategories('foo', 'bar');

        $this->assertEquals(['categories' => ['foo', 'bar']], $context->streamsNames());
    }

    /**
     * @test
     */
    public function it_set_all_streams(): void
    {
        $context = $this->newProjectorContext(false);

        $context->fromAll();

        $this->assertEquals(['all' => true], $context->streamsNames());
    }

    /**
     * @test
     */
    public function it_set_timer(): void
    {
        $context = $this->newProjectorContext(false);

        $context->withTimer(100);

        $this->assertInstanceOf(ProcessTimer::class, $context->timer());
    }

    /**
     * @test
     */
    public function it_set_null_timer(): void
    {
        $context = $this->newProjectorContext(false);

        $this->assertInstanceOf(NullTimer::class, $context->timer());
    }
}
