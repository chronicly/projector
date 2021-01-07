<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Unit\Factory;

use Chronhub\Contracts\Messaging\MessageHeader;
use Chronhub\Foundation\Exception\StreamNotFound;
use Chronhub\Foundation\Message\Message;
use Chronhub\Projector\Exception\RuntimeException;
use Chronhub\Projector\Factory\StreamEventIterator;
use Chronhub\Projector\Tests\TestCase;
use Generator;
use Illuminate\Support\LazyCollection;

final class StreamEventIteratorTest extends TestCase
{
    private array $events = [];
    private ?Message $invalidEvent = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->events = [
            new Message(SomeDomainEvent::fromPayload(['foo' => 'bar']), [
                MessageHeader::INTERNAL_POSITION => 1
            ]),
            new Message(SomeDomainEvent::fromPayload(['foo' => 'baz']), [
                MessageHeader::INTERNAL_POSITION => 2
            ])
        ];

        $this->invalidEvent =
            new Message(SomeDomainEvent::fromPayload(['foo' => 'bar']), [
                MessageHeader::INTERNAL_POSITION => 0
            ]);
    }

    /**
     * @test
     */
    public function it_can_be_constructed_with_events_generator(): void
    {
        $iterator = new StreamEventIterator($this->provideEvents());

        $this->assertEquals($this->events[0], $iterator->current());
        $this->assertEquals($iterator->key(), $this->events[0]->header(MessageHeader::INTERNAL_POSITION));

        $iterator->next();

        $this->assertEquals($this->events[1], $iterator->current());
        $this->assertEquals($iterator->key(), $this->events[1]->header(MessageHeader::INTERNAL_POSITION));
    }

    /**
     * @test
     */
    public function it_raise_exception_with_invalid_internal_position_header(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream event position must be greater than 0');

        new StreamEventIterator($this->provideInvalidEvents());
    }

    /**
     * @test
     */
    public function it_catch_stream_not_found_will_result_with_empty_iterator(): void
    {
        $iterator = new StreamEventIterator($this->provideStreamNotFoundWhileIterating());

        $this->assertFalse($iterator->key());
        $this->assertNull($iterator->current());
        $this->assertFalse($iterator->valid());
    }

    public function provideEvents(): Generator
    {
        yield $this->events[0];

        yield $this->events[1];
    }

    public function provideInvalidEvents(): Generator
    {
        yield $this->invalidEvent;
    }

    public function provideStreamNotFoundWhileIterating(): Generator
    {
        yield from (new LazyCollection())->whenEmpty(function () {
            throw new StreamNotFound('foo');
        });
    }
}
