<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Feature;

use Chronhub\Foundation\Aggregate\AggregateChanged;
use Chronhub\Projector\Exception\InvalidArgumentException;
use Chronhub\Projector\Tests\Double\User\UsernameChanged;
use Chronhub\Projector\Tests\Double\User\UserRegistered;
use Chronhub\Projector\Tests\InMemoryTestWithOrchestra;

final class ItProjectQueryTest extends InMemoryTestWithOrchestra
{
    /**
     * @test
     */
    public function it_project_query_and_reset_state_on_each_run(): void
    {
        $this->setupFirstCommit();

        $projector = $this->projectorManager;

        $projection = $projector->createQuery()
            ->initialize(fn(): array => ['username' => 'invalid_user_name', 'count' => 0])
            ->withQueryFilter($projector->queryScope()->fromIncludedPosition())
            ->fromStreams($this->streamName->toString())
            ->whenAny(function (AggregateChanged $event, array $state): array {
                if ($event instanceof UserRegistered) {
                    $state ['username'] = $event->toPayload()['name'];
                    $state['count']++;
                }

                if ($event instanceof UsernameChanged) {
                    $state ['username'] = $event->toPayload()['new_name'];
                    $state['count']++;
                }

                return $state;
            });

        $projection->run(false);

        $this->assertEquals(['username' => $this->userName, 'count' => 1], $projection->getState());

        $this->setupSecondCommit();

        $projection->run(false);

        $this->assertEquals(['username' => $this->newUserName, 'count' => 1], $projection->getState());
    }

    /**
     * @test
     */
    public function it_project_query_once(): void
    {
        $this->setupFirstCommit();

        $this->setupSecondCommit();

        $projector = $this->projectorManager;

        $projection = $projector->createQuery()
            ->initialize(fn(): array => ['username' => 'invalid_user_name', 'count' => 0])
            ->withQueryFilter($projector->queryScope()->fromIncludedPosition())
            ->fromStreams($this->streamName->toString())
            ->whenAny(function (AggregateChanged $event, array $state): array {
                if ($event instanceof UserRegistered) {
                    $state['username'] = $event->toPayload()['name'];
                    $state['count']++;
                }

                if ($event instanceof UsernameChanged) {
                    $state['username'] = $event->toPayload()['new_name'];
                    $state['count']++;
                }

                return $state;
            });

        $projection->run(false);

        $this->assertEquals(['username' => $this->newUserName, 'count' => 2], $projection->getState());
    }

    /**
     * @test
     */
    public function it_raise_exception_if_it_project_query_in_background(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Query projection can not run in background');

        $projector = $this->projectorManager;

        $projection = $projector->createQuery()
            ->withQueryFilter($projector->queryScope()->fromIncludedPosition())
            ->fromStreams($this->streamName->toString())
            ->whenAny(function (AggregateChanged $event, array $state): void {
                //
            });

        $projection->run(true);
    }

    /**
     * @test
     */
    public function it_stop_query_projection(): void
    {
        $this->setupFirstCommit();
        $this->setupSecondCommit();

        $projector = $this->projectorManager;

        $projection = $projector->createQuery()
            ->initialize(fn(): array => ['username' => 'invalid_user_name', 'count' => 0])
            ->withQueryFilter($projector->queryScope()->fromIncludedPosition())
            ->fromStreams($this->streamName->toString())
            ->whenAny(function (AggregateChanged $event, array $state): array {
                if ($event instanceof UserRegistered) {
                    $state['username'] = $event->toPayload()['name'];
                    $state['count']++;

                    $this->stop();
                }

                if ($event instanceof UsernameChanged) {
                    $state['username'] = $event->toPayload()['new_name'];
                    $state['count']++;
                }

                return $state;
            });

        $projection->run(false);

        $this->assertEquals(['username' => $this->userName, 'count' => 1], $projection->getState());
    }
}
