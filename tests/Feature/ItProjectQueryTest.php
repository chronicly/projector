<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Feature;

use Chronhub\Foundation\Aggregate\AggregateChanged;
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
        $test= $this;

        $this->setupFirstCommit();

        $projector = $this->projectorManager;

        $projection = $projector->createQuery()
            ->initialize(fn(): array => ['username' => 'invalid_user_name', 'count' => 0])
            ->withQueryFilter($projector->queryScope()->fromIncludedPosition())
            ->fromStreams($this->streamName->toString())
            ->whenAny(function (AggregateChanged $event, array $state)use($test): array {
                if ($event instanceof UserRegistered) {
                    $state ['username'] = $event->toPayload()['name'];
                    $state['count']++;
                }

                if ($event instanceof UsernameChanged) {
                    $state ['username'] = $event->toPayload()['new_name'];
                    $state['count']++;
                }

                $test->assertEquals('user', $this->streamName());

                return $state;
            });

        $projection->run(false);

        $this->assertEquals(['username' => $this->username, 'count' => 1], $projection->getState());

        $this->setupSecondCommit();

        $projection->run(false);

        $this->assertEquals(['username' => $this->newUsername, 'count' => 1], $projection->getState());
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

        $this->assertEquals(['username' => $this->newUsername, 'count' => 2], $projection->getState());
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

        $this->assertEquals(['username' => $this->username, 'count' => 1], $projection->getState());
    }

    /**
     * @test
     */
    public function it_reset_query_projection_to_initial_state(): void
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

        $this->assertEquals(['username' => $this->newUsername, 'count' => 2], $projection->getState());

        $projection->reset();

        $this->assertEquals(['username' => 'invalid_user_name', 'count' => 0], $projection->getState());
    }
}
