<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Unit\Factory;

use Chronhub\Contracts\Model\EventStreamProvider;
use Chronhub\Projector\Exception\RuntimeException;
use Chronhub\Projector\Factory\StreamPosition;
use Chronhub\Projector\Tests\TestCaseWithProphecy;
use Prophecy\Prophecy\ObjectProphecy;

final class StreamPositionTest extends TestCaseWithProphecy
{
    private EventStreamProvider|ObjectProphecy $eventStreamProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventStreamProvider = $this->prophesize(EventStreamProvider::class);
    }

    /**
     * @test
     */
    public function it_can_be_constructed_with_empty_streams(): void
    {
        $streamsPositions = new StreamPosition($this->eventStreamProvider->reveal());

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

        $streamsPosition = new StreamPosition($this->eventStreamProvider->reveal());
        $streamsPosition->make(['all' => true]);

        $this->assertEquals([
            'foo' => 0,
            'bar' => 0
        ], $streamsPosition->all());
    }

    /**
     * @test
     */
    public function it_gather_provided_stream_names(): void
    {
        $fooStream = 'foo';
        $barStream = 'bar';

        $this->eventStreamProvider
            ->filterByStreams([$fooStream, $barStream])
            ->willReturn([$fooStream, $barStream])
            ->shouldBeCalled();

        $streamsPosition = new StreamPosition($this->eventStreamProvider->reveal());
        $streamsPosition->make(['names' => ['foo', 'bar']]);

        $this->assertEquals([
            'foo' => 0,
            'bar' => 0
        ], $streamsPosition->all());
    }

    /**
     * @test
     */
    public function it_raise_exception_if_provided_stream_names_are_empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid configuration, stream names can not be empty');

        $this->eventStreamProvider->filterByStreams([])->shouldNotBeCalled();

        $streamsPosition = new StreamPosition($this->eventStreamProvider->reveal());
        $streamsPosition->make([]);
    }

    /**
     * @test
     */
    public function it_raise_exception_if_at_least_one_provided_stream_name_does_not_exists(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('One or many stream names were not found in event stream table');

        $fooStream = 'foo';
        $barStream = 'bar';

        $this->eventStreamProvider
            ->filterByStreams([$fooStream, $barStream])
            ->willReturn([$barStream])
            ->shouldBeCalled();

        $streamsPosition = new StreamPosition($this->eventStreamProvider->reveal());
        $streamsPosition->make(['names' => ['foo', 'bar']]);

        $this->assertEquals([
            'foo' => 0,
            'bar' => 0
        ], $streamsPosition->all());
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

        $streamsPosition = new StreamPosition($this->eventStreamProvider->reveal());
        $streamsPosition->make(['categories' => ['foo-123', 'bar-124']]);

        $this->assertEquals(['foo-123' => 0, 'bar-124' => 0], $streamsPosition->all());
    }

    /**
     * @test
     */
    public function it_can_merge_streams_when_loading_state_from_remote(): void
    {
        $this->eventStreamProvider
            ->filterByStreams(['foo'])
            ->willReturn(['foo'])
            ->shouldBeCalled();

        $streamsPosition = new StreamPosition($this->eventStreamProvider->reveal());
        $streamsPosition->make(['names' => ['foo']]);

        $streamsPosition->merge(['bar' => 1]);

        $this->assertEquals([
            'foo' => 0,
            'bar' => 1
        ], $streamsPosition->all());
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

        $streamsPosition = new StreamPosition($this->eventStreamProvider->reveal());
        $streamsPosition->make(['all' => true]);

        $this->assertEquals([
            'foo' => 0,
            'bar' => 0
        ], $streamsPosition->all());

        $streamsPosition->reset();

        $this->assertEquals([], $streamsPosition->all());
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

        $streamsPosition = new StreamPosition($this->eventStreamProvider->reveal());
        $streamsPosition->make(['all' => true]);

        $this->assertEquals([
            'foo' => 0,
            'bar' => 0
        ], $streamsPosition->all());

        $streamsPosition->setAt('foo', 5);
        $streamsPosition->setAt('bar', 10);

        $this->assertEquals([
            'foo' => 5,
            'bar' => 10
        ], $streamsPosition->all());
    }
}
