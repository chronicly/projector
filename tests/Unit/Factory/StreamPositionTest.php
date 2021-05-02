<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Unit\Factory;

use Chronhub\Contracts\Clock\Clock;
use Chronhub\Contracts\Model\EventStreamProvider;
use Chronhub\Projector\Exception\RuntimeException;
use Chronhub\Projector\Factory\StreamPosition;
use Chronhub\Projector\Tests\TestCaseWithProphecy;
use Prophecy\Prophecy\ObjectProphecy;

final class StreamPositionTest extends TestCaseWithProphecy
{
    private EventStreamProvider|ObjectProphecy $eventStreamProvider;
    private Clock|ObjectProphecy $clock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventStreamProvider = $this->prophesize(EventStreamProvider::class);
        $this->clock = $this->prophesize(Clock::class);
    }

    protected function streamPositionInstance(): \Chronhub\Contracts\Projecting\StreamPosition
    {
        return new StreamPosition(
            $this->eventStreamProvider->reveal(),
            $this->clock->reveal(),
            ['1', '5', '10'],
            'PT10S'
        );
    }

    /**
     * @test
     */
    public function it_can_be_constructed_with_empty_streams(): void
    {
        $streamsPositions = $this->streamPositionInstance();

        $this->assertEmpty($streamsPositions->all());
    }

    /**
     * @test
     */
    public function it_gather_all_streams(): void
    {
        $this->eventStreamProvider
            ->allStreamWithoutInternal()
            ->willReturn(['foo', 'bar'])
            ->shouldBeCalled();

        $streamsPositions = $this->streamPositionInstance();

        $streamsPositions->watch(['all' => true]);

        $this->assertEquals([
            'foo' => 0,
            'bar' => 0
        ], $streamsPositions->all());
    }

    /**
     * @test
     */
    public function it_gather_provided_stream_names(): void
    {
        $streamsPositions = $this->streamPositionInstance();

        $streamsPositions->watch(['names' => ['foo', 'bar']]);

        $this->assertEquals([
            'foo' => 0,
            'bar' => 0
        ], $streamsPositions->all());
    }

    /**
     * @test
     */
    public function it_raise_exception_if_provided_stream_names_are_empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid configuration, stream names can not be empty');

        $this->eventStreamProvider->filterByStreams([])->shouldNotBeCalled();

        $streamsPositions = $this->streamPositionInstance();

        $streamsPositions->watch([]);
    }

    /**
     * @test
     */
    public function it_gather_provided_categories(): void
    {
        $this->eventStreamProvider
            ->filterByCategories(['foo-123', 'bar-124'])
            ->willReturn(['foo-123', 'bar-124'])
            ->shouldBeCalled();

        $streamsPositions = $this->streamPositionInstance();

        $streamsPositions->watch(['categories' => ['foo-123', 'bar-124']]);

        $this->assertEquals(['foo-123' => 0, 'bar-124' => 0], $streamsPositions->all());
    }

    /**
     * @test
     */
    public function it_can_merge_streams_when_loading_state_from_remote(): void
    {
        $streamsPositions = $this->streamPositionInstance();

        $streamsPositions->watch(['names' => ['foo']]);

        $streamsPositions->discover(['bar' => 1]);

        $this->assertEquals([
            'foo' => 0,
            'bar' => 1
        ], $streamsPositions->all());
    }

    /**
     * @test
     */
    public function it_can_reset_stream_positions(): void
    {
        $this->eventStreamProvider
            ->allStreamWithoutInternal()
            ->willReturn(['foo', 'bar'])
            ->shouldBeCalled();

        $streamsPositions = $this->streamPositionInstance();

        $streamsPositions->watch(['all' => true]);

        $this->assertEquals([
            'foo' => 0,
            'bar' => 0
        ], $streamsPositions->all());

        $streamsPositions->reset();

        $this->assertEquals([], $streamsPositions->all());
    }

    /**
     * @test
     */
    public function it_can_set_stream_at_defined_position(): void
    {
        $this->eventStreamProvider
            ->allStreamWithoutInternal()
            ->willReturn(['foo', 'bar'])
            ->shouldBeCalled();

        $streamsPositions = $this->streamPositionInstance();

        $streamsPositions->watch(['all' => true]);

        $this->assertEquals([
            'foo' => 0,
            'bar' => 0
        ], $streamsPositions->all());

        $streamsPositions->bind('foo', 5);
        $streamsPositions->bind('bar', 10);

        $this->assertEquals([
            'foo' => 5,
            'bar' => 10
        ], $streamsPositions->all());
    }
}
