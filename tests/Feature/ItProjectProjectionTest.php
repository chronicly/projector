<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Feature;

use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Contracts\Aggregate\AggregateChanged;
use Chronhub\Contracts\Messaging\MessageHeader;
use Chronhub\Contracts\Stream\NamedStream;
use Chronhub\Projector\Tests\Double\User\UsernameChanged;
use Chronhub\Projector\Tests\Double\User\UserRegistered;
use Chronhub\Projector\Tests\InMemoryTestWithOrchestra;
use RuntimeException;

final class ItProjectProjectionTest extends InMemoryTestWithOrchestra
{
    private NamedStream $projectionStreamName;

    /**
     * @test
     */
    public function it_emit_event(): void
    {
        $this->assertFalse($this->projectorManager->exists($this->projectionStreamName->toString()));

        $this->setupFirstCommit();

        $projection = $this->projectorManager->createProjection(
            'user-' . $this->aggregateId->toString()
        );

        $projection
            ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
            ->fromStreams($this->streamName->toString())
            ->whenAny(function (AggregateChanged $event, array $state): array {
                $this->emit($event);

                return $state;
            });

        $projection->run(false);

        $this->setupSecondCommit();

        $projection->run(false);

        $this->assertEquals([], $projection->getState());
        $this->assertTrue($this->projectorManager->exists($this->projectionStreamName->toString()));
    }

    /**
     * @test
     */
    public function it_link_event_to_another_projection(): void
    {
        $this->setupFirstCommit();

        $this->assertTrue($this->chronicler->hasStream($this->streamName));

        $projection = $this->projectorManager->createProjection('user');

        $projection
            ->initialize(fn(): array => ['count' => 0])
            ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
            ->fromStreams('user')
            ->whenAny(function (AggregateChanged $event, array $state): array {
                $aggregateId = $event->header(MessageHeader::AGGREGATE_ID);

                $this->linkTo('user-' . $aggregateId->toString(), $event);

                $state['count']++;

                return $state;
            });

        $projection->run(false);

        $this->assertEquals(1, $projection->getState()['count']);

        $this->setupSecondCommit();

        $projection->run(false);

        $this->assertEquals(2, $projection->getState()['count']);

        $this->assertTrue($this->projectorManager->exists($this->streamName->toString()));

        $this->assertTrue($this->chronicler->hasStream($this->projectionStreamName));
    }

    /**
     * @test
     */
    public function it_stop_projection(): void
    {
        $this->setupFirstCommit();
        $this->setupSecondCommit();

        $projection = $this->projectorManager->createProjection('user');

        $projection
            ->initialize(fn(): array => ['count' => 0])
            ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
            ->fromStreams('user')
            ->whenAny(function (AggregateChanged $event, array $state): array {
                if ($event instanceof UserRegistered) {
                    $state['count']++;
                    $this->emit($event);
                    $this->stop();
                }

                if ($event instanceof UsernameChanged) {
                    throw new RuntimeException("Should not be called");
                }

                return $state;
            });

        $projection->run(false);

        $this->assertEquals(1, $projection->getState()['count']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectionStreamName = new StreamName('user-' . $this->aggregateId->toString());
    }
}
