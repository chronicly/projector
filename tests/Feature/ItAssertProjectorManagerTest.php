<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Feature;

use Chronhub\Contracts\Aggregate\AggregateChanged;
use Chronhub\Contracts\Projecting\ProjectionStatus;
use Chronhub\Projector\Exception\ProjectionNotFound;
use Chronhub\Projector\Tests\Double\User\UserRegistered;
use Chronhub\Projector\Tests\InMemoryTestWithOrchestra;

final class ItAssertProjectorManagerTest extends InMemoryTestWithOrchestra
{
    /**
     * @test
     */
    public function it_assert_fields_during_projection(): void
    {
        $test = $this;

        $this->setupFirstCommit();

        $projector = $this->projectorManager;

        $this->assertFalse($projector->exists('user'));

        $projection = $projector->createProjection('user');
        $projection
            ->initialize(fn(): array => ['run' => false])
            ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
            ->fromStreams($this->streamName->toString())
            ->whenAny(function (AggregateChanged $event, array $state) use ($test, $projector): array {
                $test->assertEquals(ProjectionStatus::RUNNING, $projector->statusOf('user'));
                $test->assertEquals([], $projector->streamPositionsOf('user'));
                $test->assertEquals([], $projector->stateOf('user'));

                if ($event instanceof UserRegistered) {
                    $state['run'] = true;
                }

                return $state;
            })->run(false);

        $this->assertTrue($projector->exists('user'));
        $this->assertEquals(ProjectionStatus::IDLE, $projector->statusOf('user'));
        $this->assertEquals(["user" => 1], $projector->streamPositionsOf('user'));
        $this->assertEquals(["run" => true], $projector->stateOf('user'));
        $this->assertTrue($projection->getState()['run']);
    }

    /**
     * @test
     */
    public function it_reset_projection(): void
    {
        $test = $this;

        $this->setupFirstCommit();

        $projector = $this->projectorManager;

        $this->assertFalse($projector->exists('user'));

        $projection = $projector->createProjection('user');
        $projection
            ->initialize(fn(): array => ['run' => false])
            ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
            ->fromStreams($this->streamName->toString())
            ->whenAny(function (AggregateChanged $event, array $state) use ($test, $projector): array {
                $test->assertEquals(ProjectionStatus::RUNNING, $projector->statusOf('user'));
                $test->assertEquals([], $projector->streamPositionsOf('user'));
                $test->assertEquals([], $projector->stateOf('user'));

                $state['run'] = true;

                return $state;
            });

        $projection->run(false);

        $projector->reset('user');

        sleep(1);

        $this->assertTrue($projector->exists('user'));
        $this->assertEquals(ProjectionStatus::RESETTING, $projector->statusOf('user'));
        $this->assertEquals(["user" => 1], $projector->streamPositionsOf('user'));
        $this->assertEquals(["run" => true], $projector->stateOf('user'));
        $this->assertTrue($projection->getState()['run']);

        $projection->run(false);

        $this->assertTrue($projector->exists('user'));
        $this->assertEquals(ProjectionStatus::IDLE, $projector->statusOf('user'));
        $this->assertEquals(["user" => 0], $projector->streamPositionsOf('user'));
        $this->assertEquals(["run" => false], $projector->stateOf('user'));
        $this->assertFalse($projection->getState()['run']);
    }

    /**
     * @test
     */
    public function it_delete_projection(): void
    {
        $test = $this;

        $this->setupFirstCommit();

        $projector = $this->projectorManager;

        $this->assertFalse($projector->exists('user'));

        $projection = $projector->createProjection('user');
        $projection
            ->initialize(fn(): array => ['run' => false])
            ->withQueryFilter($projector->queryScope()->fromIncludedPosition())
            ->fromStreams($this->streamName->toString())
            ->whenAny(function (AggregateChanged $event, array $state) use ($test, $projector): array {
                $test->assertEquals(ProjectionStatus::RUNNING, $projector->statusOf('user'));
                $test->assertEquals([], $projector->streamPositionsOf('user'));
                $test->assertEquals([], $projector->stateOf('user'));

                $state['run'] = true;

                return $state;
            });

        $projection->run(false);

        $projector->delete('user', false);

        $this->assertTrue($projector->exists('user'));
        $this->assertEquals(ProjectionStatus::DELETING, $projector->statusOf('user'));
        $this->assertEquals(["user" => 1], $projector->streamPositionsOf('user'));
        $this->assertEquals(["run" => true], $projector->stateOf('user'));
        $this->assertTrue($projection->getState()['run']);

        $projection->run(false);

        $this->assertFalse($projector->exists('user'));
        $this->assertFalse($projection->getState()['run']);
    }

    /**
     * @test
     */
    public function it_delete_projection_with_emitted_events(): void
    {
        $test = $this;

        $this->setupFirstCommit();

        $projector = $this->projectorManager;

        $this->assertFalse($projector->exists('user'));

        $projection = $projector->createProjection('user');
        $projection
            ->initialize(fn(): array => ['run' => false])
            ->withQueryFilter($projector->queryScope()->fromIncludedPosition())
            ->fromStreams($this->streamName->toString())
            ->whenAny(function (AggregateChanged $event, array $state) use ($test, $projector): array {
                $test->assertEquals(ProjectionStatus::RUNNING, $projector->statusOf('user'));
                $test->assertEquals([], $projector->streamPositionsOf('user'));
                $test->assertEquals([], $projector->stateOf('user'));

                $state['run'] = true;

                return $state;
            });

        $projection->run(false);

        $projector->delete('user', true);

        $this->assertTrue($projector->exists('user'));
        $this->assertEquals(ProjectionStatus::DELETING_EMITTED_EVENTS, $projector->statusOf('user'));
        $this->assertEquals(["user" => 1], $projector->streamPositionsOf('user'));
        $this->assertEquals(["run" => true], $projector->stateOf('user'));
        $this->assertTrue($projection->getState()['run']);

        $projection->run(false);

        $this->assertFalse($projector->exists('user'));
        $this->assertFalse($projection->getState()['run']);
    }

    /**
     * @test
     */
    public function it_stop_projection(): void
    {
        $test = $this;

        $this->setupFirstCommit();

        $projector = $this->projectorManager;

        $projection = $projector->createProjection('user');
        $projection
            ->withQueryFilter($projector->queryScope()->fromIncludedPosition())
            ->fromStreams($this->streamName->toString())
            ->whenAny(function () use ($test, $projector): void {
                 $test->assertEquals(ProjectionStatus::RUNNING, $projector->statusOf('user'));
            })
            ->run(false);

        $projector->stop($this->streamName->toString());

        $this->assertEquals(ProjectionStatus::STOPPING, $projector->statusOf($this->streamName->toString()));

        $projection->run(false);

        $this->assertEquals(ProjectionStatus::IDLE, $projector->statusOf($this->streamName->toString()));
    }

    /**
     * @test
     */
    public function it_raise_projection_not_found_while_fetching_state_position(): void
    {
        $this->expectException(ProjectionNotFound::class);

        $this->projectorManager->stateOf('invalid_stream');
    }

    /**
     * @test
     */
    public function it_raise_projection_not_found_while_fetching_stream_position(): void
    {
        $this->expectException(ProjectionNotFound::class);

        $this->projectorManager->streamPositionsOf('invalid_stream');
    }

    /**
     * @test
     */
    public function it_raise_projection_not_found_while_fetching_stream_status(): void
    {
        $this->expectException(ProjectionNotFound::class);

        $this->projectorManager->statusOf('invalid_stream');
    }

    /**
     * @test
     */
    public function it_raise_projection_not_found_while_fetching_stream_state(): void
    {
        $this->expectException(ProjectionNotFound::class);

        $this->projectorManager->stateOf('invalid_stream');
    }
}
