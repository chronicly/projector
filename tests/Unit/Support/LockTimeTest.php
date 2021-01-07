<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Unit\Support;

use Chronhub\Projector\Support\LockTime;
use Chronhub\Projector\Tests\TestCase;
use DateTimeImmutable;
use DateTimeZone;
use Generator;
use ReflectionClass;
use ReflectionProperty;

final class LockTimeTest extends TestCase
{
    private string $now = '2020-12-29T08:49:07.000000';

    /**
     * @test
     */
    public function it_generate_date_time(): void
    {
        $lockTime = LockTime::fromNow();

        $this->assertEquals('UTC', $lockTime::TIMEZONE);
        $this->assertEquals('Y-m-d\TH:i:s.u', $lockTime::FORMAT);
    }

    /**
     * @test
     * @dataProvider provideLockTimeOutMsAndExpectations
     * @param int    $lockTimeoutMs
     * @param string $expectedLock
     */
    public function it_add_milliseconds_to_current_date(int $lockTimeoutMs, string $expectedLock): void
    {
        $now = new DateTimeImmutable($this->now, new DateTimeZone('UTC'));

        $instance = $this->newInstanceWithTime($now);

        $this->assertEquals($now, $instance->toDateTime());

        $this->assertEquals($expectedLock, $instance->createLockUntil($lockTimeoutMs));
    }

    /**
     * @test
     */
    public function it_can_be_serialized(): void
    {
        $now = new DateTimeImmutable($this->now, new DateTimeZone('UTC'));

        $instance = $this->newInstanceWithTime($now);

        $this->assertEquals($this->now, $instance->toString());
    }

    public function provideLockTimeOutMsAndExpectations(): Generator
    {
        yield [10, '2020-12-29T08:49:07.10000'];

        yield [100, '2020-12-29T08:49:07.100000'];

        yield [1000, '2020-12-29T08:49:08.000000'];

        yield [10000, '2020-12-29T08:49:17.000000'];
    }

    private function newInstanceWithTime(DateTimeImmutable $now): LockTime
    {
        $class = new ReflectionClass(LockTime::class);
        $constructor = $class->getConstructor();
        $constructor->setAccessible(true);

        $lock = $class->newInstanceWithoutConstructor();

        $prop = new ReflectionProperty($lock, ('dateTime'));
        $prop->setAccessible(true);
        $prop->setValue($lock, $now);

        return $lock;
    }
}
