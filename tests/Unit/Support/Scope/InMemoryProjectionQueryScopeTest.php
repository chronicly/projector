<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Unit\Support\Scope;

use Chronhub\Contracts\Messaging\MessageHeader;
use Chronhub\Foundation\Message\Message;
use Chronhub\Projector\Exception\RuntimeException;
use Chronhub\Projector\Support\Scope\InMemoryProjectionQueryScope;
use Chronhub\Projector\Tests\TestCase;
use Generator;
use stdClass;

final class InMemoryProjectionQueryScopeTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideMessages
     * @param array $messages
     * @param int   $expectedCount
     * @param int   $position
     */
    public function it_filter_messages_by_internal_position_header(array $messages, int $expectedCount, int $position): void
    {
        $scope = new InMemoryProjectionQueryScope();
        $filter = $scope->fromIncludedPosition();
        $filter->setCurrentPosition($position);

        $messages = array_filter($messages, $filter->filterQuery());

        $this->assertCount($expectedCount, $messages);
    }

    /**
     * @test
     */
    public function it_raise_exception_when_position_is_not_set(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Position must be greater than 0, current is 0");

        $scope = new InMemoryProjectionQueryScope();
        $filter = $scope->fromIncludedPosition();

        $filter->filterQuery();
    }

    /**
     * @test
     * @dataProvider provideInvalidPosition
     * @param int $invalidPosition
     */
    public function it_raise_exception_when_position_is_less_than_zero(int $invalidPosition): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Position must be greater than 0, current is $invalidPosition");

        $scope = new InMemoryProjectionQueryScope();
        $filter = $scope->fromIncludedPosition();
        $filter->setCurrentPosition($invalidPosition);

        $filter->filterQuery();
    }

    public function provideMessages(): Generator
    {
        $messages = [
            new Message(new stdClass(), [
                MessageHeader::INTERNAL_POSITION => 3,
            ]),
            new Message(new stdClass(), [
                MessageHeader::INTERNAL_POSITION => 1,
            ]),
            new Message(new stdClass(), [
                MessageHeader::INTERNAL_POSITION => 2,
            ]),
            new Message(new stdClass(), [
                MessageHeader::INTERNAL_POSITION => 4,
            ]),
        ];

        yield [$messages, 4, 1];

        yield [$messages, 2, 3];

        yield [$messages, 1, 4];

        yield [$messages, 0, 5];
    }

    public function provideInvalidPosition():Generator
    {
        yield [0];
        yield [-1];
    }
}
