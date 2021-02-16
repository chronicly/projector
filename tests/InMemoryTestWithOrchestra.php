<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests;

use Chronhub\Chronicler\ChroniclerServiceProvider;
use Chronhub\Chronicler\Driver\InMemory\InMemoryEventStream;
use Chronhub\Chronicler\Driver\InMemory\InMemoryQueryScope;
use Chronhub\Chronicler\Stream\Stream;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Contracts\Aggregate\AggregateId;
use Chronhub\Contracts\Chronicling\Chronicler;
use Chronhub\Contracts\Chronicling\InMemoryChronicler;
use Chronhub\Contracts\Manager\ChroniclerManager;
use Chronhub\Contracts\Manager\ProjectorServiceManager;
use Chronhub\Contracts\Messaging\MessageHeader;
use Chronhub\Contracts\Projecting\ProjectorManager;
use Chronhub\Contracts\Projecting\ProjectorOption;
use Chronhub\Contracts\Stream\NamedStream;
use Chronhub\Foundation\ChronhubServiceProvider;
use Chronhub\Foundation\Clock\SystemClock;
use Chronhub\Foundation\Message\Message;
use Chronhub\Projector\Model\InMemoryProjectionProvider;
use Chronhub\Projector\ProjectorServiceProvider;
use Chronhub\Projector\Tests\Double\User\UserId;
use Chronhub\Projector\Tests\Double\User\UsernameChanged;
use Chronhub\Projector\Tests\Double\User\UserRegistered;
use Illuminate\Contracts\Foundation\Application;

abstract class InMemoryTestWithOrchestra extends TestWithOrchestra
{
    protected InMemoryChronicler $chronicler;
    protected ProjectorManager $projectorManager;
    protected NamedStream $streamName;
    protected AggregateId $aggregateId;
    protected string $username = 'steph';
    protected string $newUsername = 'fab';

    protected function setUp(): void
    {
        parent::setUp();

        $this->chronicler = $this->app->make(InMemoryChronicler::class);
        $this->app->alias(InMemoryChronicler::class, Chronicler::class);
        $this->projectorManager = $this->app->make(ProjectorManager::class);
        $this->streamName = new StreamName('user');
        $this->aggregateId = UserId::create();
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('chronicler.connections', [
            'in_memory' => [
                'driver' => 'in_memory',
                'strategy' => 'single',
                'provider' => 'in_memory',
                'use_transaction' => false,
                'scope' => InMemoryQueryScope::class,
            ]]);

        $app['config']->set('projector.options', [
            'in_memory' => [
                ProjectorOption::OPTION_PCNTL_DISPATCH => false,
                ProjectorOption::OPTION_LOCK_TIMEOUT_MS => 0,
                ProjectorOption::OPTION_SLEEP => 0,
                ProjectorOption::OPTION_UPDATE_LOCK_THRESHOLD => 0,
                ProjectorOption::OPTION_PERSIST_BLOCK_SIZE => 1,
            ]
        ]);
    }

    protected function getPackageProviders($app): array
    {
        $app->register(ChronhubServiceProvider::class);
        $app->register(ChroniclerServiceProvider::class);
        $app->register(ProjectorServiceProvider::class);

        return [];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app->singleton(InMemoryEventStream::class);
        $app->singleton(InMemoryProjectionProvider::class);

        $app->singleton(InMemoryChronicler::class, function (Application $app): Chronicler {
            return $app[ChroniclerManager::class]->create('in_memory');
        });

        $app->bind(ProjectorManager::class, function (Application $app): ProjectorManager {
            return $app[ProjectorServiceManager::class]->create('in_memory');
        });
    }

    protected function setupFirstCommit(): void
    {
        $this->assertFalse($this->chronicler->hasStream($this->streamName));

        $event = UserRegistered::withData($this->aggregateId, $this->username);

        $headers = [
            MessageHeader::AGGREGATE_ID => $this->aggregateId,
            MessageHeader::INTERNAL_POSITION => 1,
            MessageHeader::TIME_OF_RECORDING => (new SystemClock())->pointInTime(),
        ];

        $stream = new Stream($this->streamName, [new Message($event, $headers)]);

        $this->chronicler->persistFirstCommit($stream);

        $this->assertTrue($this->chronicler->hasStream($this->streamName));
    }

    protected function setupSecondCommit(): void
    {
        $this->assertTrue($this->chronicler->hasStream($this->streamName));

        $event = UsernameChanged::withName(
            $this->aggregateId, $this->newUsername, $this->username
        );

        $message = new Message($event, [
            MessageHeader::AGGREGATE_ID => $this->aggregateId,
            MessageHeader::INTERNAL_POSITION => 2,
            MessageHeader::TIME_OF_RECORDING => (new SystemClock())->pointInTime(),
        ]);

        $stream = new Stream($this->streamName, [$message]);

        $this->chronicler->persist($stream);
    }
}
