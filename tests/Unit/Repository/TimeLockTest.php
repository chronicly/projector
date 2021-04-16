<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Unit\Repository;

use Chronhub\Projector\Repository\TimeLock;
use Chronhub\Projector\Tests\TestCase;
use DateTimeImmutable;

final class TimeLockTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_instantiated_with_null_lock(): void
    {
        $lock = new TimeLock(1, 1);

        $this->assertNull($lock->lastLockUpdate());
    }

    /**
     * @test
     */
    public function it_acquire_lock(): void
    {
        $timeout = 5000;
        $threshold = 1000;

        $locker = new TimeLock($timeout, $threshold);

        [$lock, $now] = $locker->acquire();

        $diff = (new DateTimeImmutable($lock))->diff(new DateTimeImmutable($now));

        $this->assertEquals($diff->s, $timeout / 1000);

        $this->assertEquals($locker->lastLockUpdate(), new DateTimeImmutable($now));
    }

    /**
     * @test
     */
    public function it_increment_lock_on_update(): void
    {
        $this->markTestIncomplete('wip');

        $timeout = 1;
        $threshold = 1000;

        $locker = new TimeLock($timeout, $threshold);

        $locker->acquire();

        $this->assertFalse($locker->updateCurrentLock());

        sleep($threshold / 1000);

        $this->assertTrue($locker->updateCurrentLock());
    }

    /**
     * @test
     */
    public function it_update_current_lock_if_lock_is_null(): void
    {
        $timeout = 5000000;
        $threshold = 1000;

        $locker = new TimeLock($timeout, $threshold);

        $this->assertTrue($locker->updateCurrentLock());
    }

    /**
     * @test
     */
    public function it_update_current_lock_if_threshold_is_zero(): void
    {
        $timeout = 5000000;
        $threshold = 0;

        $locker = new TimeLock($timeout, $threshold);

        $this->assertTrue($locker->updateCurrentLock());
    }

    /**
     * @test
     */
    public function it_produce_string_lock_from_current_lock(): void
    {
        $timeout = 5000;
        $threshold = 1000;

        $locker = new TimeLock($timeout, $threshold);

        [$lock] = $locker->acquire();

        $lockString = $locker->current();

        $this->assertEquals($lock, $lockString);
    }

    /**
     * @test
     */
    public function it_produce_string_lock_from_current_time(): void
    {
        $timeout = 5000;
        $threshold = 1000;

        $locker = new TimeLock($timeout, $threshold);

        [$lock, $now] = $locker->acquire();

        $lockString = $locker->refreshLockFromNow();

        $this->assertNotEquals($lock, $lockString);
        $this->assertNotEquals($now, $lockString);

        $diff = ((new DateTimeImmutable($lockString)))->diff(new DateTimeImmutable($now));

        $this->assertEquals($diff->s, $timeout / 1000);
    }
}
