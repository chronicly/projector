<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Unit\Model;

use Chronhub\Contracts\Model\ProjectionModel;
use Chronhub\Foundation\Clock\SystemClock;
use Chronhub\Projector\Model\InMemoryProjectionProvider;
use Chronhub\Projector\Tests\TestCase;

final class InMemoryProjectionProviderTest extends TestCase
{
    /**
     * @test
     */
    public function it_create_projection(): void
    {
        $provider = new InMemoryProjectionProvider();

        $this->assertNull($provider->findByName('foo'));

        $this->assertTrue($provider->createProjection('foo', 'running'));

        $projection = $provider->findByName('foo');

        $this->assertInstanceOf(ProjectionModel::class, $projection);
    }

    /**
     * @test
     */
    public function it_update_projection(): void
    {
        $provider = new InMemoryProjectionProvider();
        $this->assertTrue($provider->createProjection('foo', 'running'));

        $projection = $provider->findByName('foo');

        $this->assertEquals('foo', $projection->name());
        $this->assertEquals('running', $projection->status());
        $this->assertEquals('{}', $projection->state());
        $this->assertEquals('{}', $projection->position());
        $this->assertNull($projection->lockedUntil());

        $this->assertFalse($provider->updateProjection('bar', []));

        $updated = $provider->updateProjection('foo', [
            'state' => '{"count" => 0}',
            'position' => '{"banking" => 0}',
            'status' => 'idle',
            'locked_until' => 'datetime'
        ]);

        $this->assertTrue($updated);

        $projection = $provider->findByName('foo');

        $this->assertEquals('foo', $projection->name());
        $this->assertEquals('idle', $projection->status());
        $this->assertEquals('{"count" => 0}', $projection->state());
        $this->assertEquals('{"banking" => 0}', $projection->position());
        $this->assertEquals('datetime', $projection->lockedUntil());
    }

    /**
     * @test
     */
    public function it_delete_projection(): void
    {
        $provider = new InMemoryProjectionProvider();

        $this->assertTrue($provider->createProjection('foo', 'running'));

        $this->assertInstanceOf(ProjectionModel::class, $provider->findByName('foo'));

        $deleted = $provider->deleteByName('foo');

        $this->assertTrue($deleted);

        $this->assertNull($provider->findByName('foo'));
    }

    /**
     * @test
     */
    public function it_find_projection_by_names(): void
    {
        $provider = new InMemoryProjectionProvider();

        $this->assertTrue($provider->createProjection('foo', 'running'));
        $this->assertTrue($provider->createProjection('bar', 'running'));

        $this->assertCount(1, $provider->findByNames('foo'));
        $this->assertCount(1, $provider->findByNames('bar'));
        $this->assertCount(2, $provider->findByNames('foo', 'bar'));
        $this->assertCount(2, $provider->findByNames('foo', 'bar', 'foo_bar'));
    }

    /**
     * @test
     */
    public function it_acquire_lock_with_null_lock_projection(): void
    {
        $provider = new InMemoryProjectionProvider();

        $this->assertTrue($provider->createProjection('foo', 'idle'));
        $this->assertNull($provider->findByName('foo')->lockedUntil());

        $now = (new SystemClock())->pointInTime();
        $lock = $now->add('PT1H');

        $provider->acquireLock('foo', 'running', $lock->toString(), $now->toString());

        $projection = $provider->findByName('foo');

        $this->assertEquals($lock->toString(), $projection->lockedUntil());
        $this->assertEquals('running', $projection->status());
    }

    /**
     * @test
     */
    public function it_acquire_lock_with_now_is_greater_than_lock_projection(): void
    {
        $provider = new InMemoryProjectionProvider();

        $this->assertTrue($provider->createProjection('foo', 'idle'));
        $this->assertNull($provider->findByName('foo')->lockedUntil());

        $now = (new SystemClock())->pointInTime();
        $lock = $now->sub('PT1H');
        $provider->acquireLock('foo', 'running', $lock->toString(), $now->toString());
        $this->assertEquals($lock->toString(), $provider->findByName('foo')->lockedUntil());

        $provider->acquireLock('foo', 'running', $now->toString(), $now->toString());
        $this->assertEquals($now->toString(), $provider->findByName('foo')->lockedUntil());
    }
}
