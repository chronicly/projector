<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests;

use PHPUnit\Framework\TestCase;

final class ItTest extends TestCase
{
    /**
     * @test
     */
    public function it_assert_true(): void
    {
        $this->assertTrue(true);
    }
}
