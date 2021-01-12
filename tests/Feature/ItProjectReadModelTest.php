<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Feature;

use Chronhub\Contracts\Aggregate\AggregateChanged;
use Chronhub\Contracts\Projecting\ReadModel;
use Chronhub\Projector\Support\ReadModel\AbstractReadModel;
use Chronhub\Projector\Tests\Double\User\InMemoryUser;
use Chronhub\Projector\Tests\Double\User\UsernameChanged;
use Chronhub\Projector\Tests\Double\User\UserRegistered;
use Chronhub\Projector\Tests\InMemoryTestWithOrchestra;
use RuntimeException;

final class ItProjectReadModelTest extends InMemoryTestWithOrchestra
{
    /**
     * @test
     */
    public function it_project_read_model(): void
    {
        $this->setupFirstCommit();

        $this->assertTrue($this->chronicler->hasStream($this->streamName));

        $readModel = $this->projectReadModel();
        $projection = $this->projectorManager->createReadModelProjection('user', $readModel);

        $projection
            ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
            ->fromStreams('user')
            ->whenAny(function (AggregateChanged $event): void {
                if ($event instanceof UserRegistered) {
                    $this->readModel()->stack('insert', $event->aggregateRootId(), $event->toPayload()['name']);
                }

                if ($event instanceof UsernameChanged) {
                    $this->readModel()->stack('update', $event->aggregateRootId(), $event->toPayload()['new_name']);
                }
            });

        $projection->run(false);

        $user = $readModel->findById($this->aggregateId->toString());

        $this->assertEquals($this->userName, $user->name());

        $this->setupSecondCommit();

        $projection->run(false);

        $this->assertTrue($this->projectorManager->exists($this->streamName->toString()));

        $user = $readModel->findById($this->aggregateId->toString());

        $this->assertEquals($this->newUserName, $user->name());
    }

    /**
     * @test
     */
    public function it_stop_read_model_projection(): void
    {
        $this->setupFirstCommit();
        $this->setupSecondCommit();

        $readModel = $this->projectReadModel();
        $projection = $this->projectorManager->createReadModelProjection('user', $readModel);

        $projection
            ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
            ->fromStreams('user')
            ->whenAny(function (AggregateChanged $event): void {
                if ($event instanceof UserRegistered) {
                    $this->readModel()->stack('insert', $event->aggregateRootId(), $event->toPayload()['name']);
                    $this->stop();
                }

                if ($event instanceof UsernameChanged) {
                    throw new RuntimeException('Should not be called');
                }
            });

        $projection->run(false);

        $user = $readModel->findById($this->aggregateId->toString());

        $this->assertEquals($this->userName, $user->name());
    }

    private function projectReadModel(): ReadModel
    {
        return new class extends AbstractReadModel {
            private array $users = [];

            public function insert(string $id, string $name): void
            {
                $this->users[] = new InMemoryUser($id, $name);
            }

            protected function update(string $id, string $name): void
            {
                foreach ($this->users as $user) {
                    if ($user->userId() === $id) {
                        $user->setName($name);
                    }
                }
            }

            public function findById(string $id): ?InMemoryUser
            {
                foreach ($this->users as $user) {
                    if ($user->userId() === $id) {
                        return $user;
                    }
                }

                return null;
            }

            public function initialize(): void
            {
                $this->users = [];
            }

            public function isInitialized(): bool
            {
                return true;
            }

            public function reset(): void
            {
                $this->users = [];
            }

            public function down(): void
            {
                $this->users = [];
            }
        };
    }
}
