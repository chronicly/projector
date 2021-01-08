<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Unit\Model;

use Chronhub\Projector\Model\InMemoryProjection;
use Chronhub\Projector\Tests\TestCase;

final class InMemoryProjectionTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_instantiated(): void
    {
        $projection = InMemoryProjection::create(
            'bank', 'running'
        );

        $this->assertEquals('bank', $projection->name());
        $this->assertEquals('running', $projection->status());
        $this->assertEquals('{}', $projection->state());
        $this->assertEquals('{}', $projection->position());
        $this->assertNull($projection->lockedUntil());
    }

    /**
     * @test
     */
    public function it_set_non_value_only(): void
    {
        $projection = InMemoryProjection::create(
            'bank', 'running'
        );

        $projection->setState(null);
        $this->assertEquals('{}', $projection->state());

        $projection->setPosition(null);
        $this->assertEquals('{}', $projection->position());
    }
}
