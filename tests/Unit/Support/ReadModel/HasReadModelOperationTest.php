<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Unit\Support\ReadModel;

use Chronhub\Projector\Support\ReadModel\HasReadModelOperation;
use Chronhub\Projector\Tests\TestCase;

final class HasReadModelOperationTest extends TestCase
{
    /**
     * @test
     */
    public function it_stack_and_persist_operation(): void
    {
        $instance = $this->readModelOperationInstance();

        $this->assertEmpty($instance->getStack());

        $instance->stack('doSomething', 'someValue', 'anotherValue');
        $stackArray = [
            [
                'doSomething',
                ['someValue', 'anotherValue']
            ]
        ];

        $this->assertEquals($stackArray, $instance->getStack());

        $instance->persist();

        $this->assertEmpty($instance->getStack());

        $this->assertEquals(['someValue', 'anotherValue'], $instance->getTodo());
    }

    private function readModelOperationInstance(): object
    {
        return new class() {
            use HasReadModelOperation;

            private array $todo = [];

            protected function doSomething(string $firstValue, string $secondValue): void
            {
                $this->todo[] = $firstValue;
                $this->todo[] = $secondValue;
            }

            public function getTodo(): array
            {
                return $this->todo;
            }

            public function getStack(): array
            {
                return $this->stack;
            }
        };
    }
}
