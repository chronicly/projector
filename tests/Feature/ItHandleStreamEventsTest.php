<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Feature;

use Chronhub\Contracts\Aggregate\AggregateChanged;
use Chronhub\Projector\Tests\Double\User\UserRegistered;
use Chronhub\Projector\Tests\InMemoryTestWithOrchestra;

final class ItHandleStreamEventsTest extends InMemoryTestWithOrchestra
{
    /**
     * @test
     */
    public function it_handle_stream_events_as_closure(): void
    {
        $this->setupFirstCommit();

        $projector = $this->projectorManager;

        $this->assertFalse($projector->exists('user'));

        $projection = $projector->createProjection('user');
        $projection
            ->initialize(fn(): array => ['run' => false])
            ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
            ->fromStreams($this->streamName->toString())
            ->whenAny(function (AggregateChanged $event, array $state): array {
                if ($event instanceof UserRegistered) {
                    $state['run'] = true;
                }

                return $state;
            })->run(false);

        $this->assertTrue($projection->getState()['run']);
    }

    /**
     * @test
     */
    public function it_handle_stream_events_as_array_with_message_alias(): void
    {
        $this->setupFirstCommit();

        $projector = $this->projectorManager;

        $this->assertFalse($projector->exists('user'));

        $projection = $projector->createProjection('user');
        $projection
            ->initialize(fn(): array => ['run' => false])
            ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
            ->fromStreams($this->streamName->toString())
            ->when(
                [
                    'user-registered' => function (UserRegistered $event, array $state): array {
                        $state['run'] = true;

                        return $state;
                    },
                ]
            )->run(false);

        $this->assertTrue($projection->getState()['run']);
    }
}
