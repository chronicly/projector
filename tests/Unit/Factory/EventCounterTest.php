<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Unit\Factory;

use Chronhub\Projector\Factory\EventCounter;
use Chronhub\Projector\Tests\TestCase;

final class EventCounterTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_constructed_with_zero_counter(): void
    {
        $counter = new EventCounter();

        $this->assertEquals(0, $counter->current());

        $this->assertTrue($counter->isReset());
    }

    /**
     * @test
     */
    public function it_can_be_incremented_and_reset(): void
    {
        $counter = new EventCounter();

        $counter->increment();
        $this->assertEquals(1, $counter->current());

        $counter->increment();
        $this->assertEquals(2, $counter->current());

        $counter->reset();

        $this->assertTrue($counter->isReset());
    }

    /**
     * @test
     */
    public function it_can_compare_counter(): void
    {
        $counter = new EventCounter();
        $this->assertTrue($counter->equals(0));

        $counter->increment();
        $this->assertTrue($counter->equals(1));

        $this->assertFalse($counter->equals(5));
    }
}
