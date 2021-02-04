<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Feature;

use Chronhub\Contracts\Model\ProjectionModel;
use Chronhub\Contracts\Model\ProjectionProvider;
use Chronhub\Contracts\Projecting\ProjectionStatus;
use Chronhub\Foundation\Clock\PointInTime;
use Chronhub\Foundation\Clock\SystemClock;
use Chronhub\Projector\Model\Projection;
use Chronhub\Projector\ProjectorServiceProvider;
use Chronhub\Projector\Support\LockTime;
use Chronhub\Projector\Tests\TestWithOrchestra;
use Illuminate\Support\Facades\Schema;

final class ItTestEloquentProjectionTest extends TestWithOrchestra
{
    private Projection|ProjectionProvider $projectionProvider;

    /**
     * @test
     */
    public function it_run_migration(): void
    {
        $this->assertTrue(Schema::hasTable(ProjectionModel::TABLE));

        $this->assertTrue(Schema::hasColumns(ProjectionModel::TABLE, [
            'no', 'name', 'position', 'state', 'locked_until'
        ]));
    }

    /**
     * @test
     */
    public function it_create_projection(): void
    {
        $this->projectionProvider->createProjection('user', 'idle');

        $this->assertTrue($this->projectionProvider->projectionExists('user'));

        /** @var Projection $model */
        $model = $this->projectionProvider->findByName('user');

        $this->assertInstanceOf(ProjectionModel::class, $model);

        $this->assertEquals(1, $model['no']);
        $this->assertEquals('user', $model['name']);
        $this->assertEquals('idle', $model['status']);
        $this->assertEquals('{}', $model['position']);
        $this->assertEquals('{}', $model['state']);
        $this->assertNull($model['locked_until']);

        $this->assertEquals(1, $model->getKey());
        $this->assertEquals('user', $model->name());
        $this->assertEquals('idle', $model->status());
        $this->assertEquals('{}', $model->position());
        $this->assertEquals('{}', $model->state());
        $this->assertNull($model->lockedUntil());
    }

    /**
     * @test
     */
    public function it_update_projection(): void
    {
        $this->projectionProvider->createProjection('user', 'idle');

        $lockedUntil = (new SystemClock())->pointInTime()->toString();

        $result = $this->projectionProvider->updateProjection('user', [
            'status' => ProjectionStatus::RUNNING,
            'position' => '{user:1}',
            'state' => '{count:1}',
            'locked_until' => $lockedUntil
        ]);

        $this->assertTrue($result);

        $model = $this->projectionProvider->findByName('user');

        $this->assertEquals(1, $model['no']);
        $this->assertEquals('user', $model['name']);
        $this->assertEquals('running', $model['status']);
        $this->assertEquals('{user:1}', $model['position']);
        $this->assertEquals('{count:1}', $model['state']);
        $this->assertEquals($lockedUntil, $model['locked_until']);
    }

    /**
     * @test
     */
    public function it_delete_projection(): void
    {
        $this->projectionProvider->createProjection('user', 'idle');

        $this->assertTrue($this->projectionProvider->projectionExists('user'));

        $result = $this->projectionProvider->deleteByName('user');

        $this->assertTrue($result);

        $this->assertFalse($this->projectionProvider->projectionExists('user'));
    }

    /**
     * @test
     */
    public function it_return_false_on_deleting_invalid_projection(): void
    {
        $this->assertFalse($this->projectionProvider->projectionExists('user'));

        $result = $this->projectionProvider->deleteByName('user');

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_find_projection_names_and_return_string_found_names_ordered_by_ascendant_name(): void
    {
        $this->projectionProvider->createProjection('user', 'idle');

        $result = $this->projectionProvider->findByNames('user');
        $this->assertEquals(['user'], $result);

        $this->projectionProvider->createProjection('bank_account', 'idle');

        $result = $this->projectionProvider->findByNames('bank_account');
        $this->assertEquals(['bank_account'], $result);

        $result = $this->projectionProvider->findByNames('user', 'bank_account');
        $this->assertEquals(['bank_account', 'user'], $result);

        $this->projectionProvider->createProjection('transactions', 'idle');

        $result = $this->projectionProvider->findByNames('user', 'bank_account', 'transactions');
        $this->assertEquals(['bank_account', 'transactions', 'user',], $result);

        $result = $this->projectionProvider->findByNames('user', 'transactions', 'not_found');
        $this->assertEquals(['transactions', 'user'], $result);
    }

    /**
     * @test
     */
    public function it_acquire_lock_and_update_status_with_null_locked_until(): void
    {
        $now = (new SystemClock)->pointInTime()->toString();

        $this->projectionProvider->createProjection('user', 'idle');

        $nullLock = $this->projectionProvider->findByName('user')->lockedUntil();

        $this->assertNull($nullLock);

        $waitTime = LockTime::fromNow()->createLockUntil(100000);

        $result = $this->projectionProvider->acquireLock('user', 'running', $waitTime, $now);

        $this->assertEquals('running', $this->projectionProvider->findByName('user')->status());
        $this->assertTrue($result);

        $newLock = $this->projectionProvider->findByName('user')->lockedUntil();

        $this->assertNotEquals($nullLock, $newLock);

        $this->assertEquals($waitTime, $newLock);
    }

    /**
     * @test
     */
    public function it_acquire_lock_and_update_status_with_locked_until_less_than_now(): void
    {
        $now = (new SystemClock)->pointInTime()->toString();

        $this->projectionProvider->createProjection('user', 'idle');

        $nullLock = $this->projectionProvider->findByName('user')->lockedUntil();
        $this->assertNull($nullLock);

        $waitTime = LockTime::fromNow()->createLockUntil(100000);

        $result = $this->projectionProvider->acquireLock('user', 'running', $waitTime, $now);
        $this->assertTrue($result);

        $this->assertEquals('running', $this->projectionProvider->findByName('user')->status());
        $newLock = $this->projectionProvider->findByName('user')->lockedUntil();

        $this->assertNotEquals($nullLock, $newLock);
        $this->assertEquals($waitTime, $newLock);

        // fail
        $result = $this->projectionProvider->acquireLock('user', 'running', $waitTime, $now);
        $this->assertFalse($result);

        //
        $now = PointInTime::fromString($now)->add('PT1H')->toString();
        $result = $this->projectionProvider->acquireLock('user', 'running', $waitTime, $now);
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_return_false_on_query_exception(): void
    {
        $this->projectionProvider->table = 'foo';

        $this->assertFalse($this->projectionProvider->projectionExists('user'));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectionProvider = new Projection();

        // fixMe service provider should load the migration itself
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        // fixMe does not register provider
        $app->register(ProjectorServiceProvider::class);

        return [];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        //$app['config']->set('projector.provider.eloquent', Projection::class);
        $app['config']->set('projector.console.load_migrations', true);
    }
}
