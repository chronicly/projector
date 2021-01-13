<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Unit\Support\Scope;

use Chronhub\Projector\Exception\RuntimeException;
use Chronhub\Projector\Support\Scope\PgsqlProjectionQueryScope;
use Chronhub\Projector\Tests\TestCaseWithProphecy;
use Illuminate\Database\Query\Builder;

final class PgsqlProjectionQueryScopeTest extends TestCaseWithProphecy
{
    /**
     * @test
     */
    public function it_filter_and_order_messages_by_ascending_position(): void
    {
        $builder = $this->prophesize(Builder::class);
        $builder->where('no', '>=', 5)->willReturn($builder)->shouldBeCalled();
        $builder->orderBy('no')->willReturn($builder)->shouldBeCalled();

        $scope = new PgsqlProjectionQueryScope();
        $filter = $scope->fromIncludedPosition();
        $filter->setCurrentPosition(5);

        $filter->filterQuery()($builder->reveal());
    }

    /**
     * @test
     */
    public function it_raise_exception_when_position_is_not_set(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Position must be greater than 0, current is 0");

        $builder = $this->prophesize(Builder::class);
        $scope = new PgsqlProjectionQueryScope();
        $filter = $scope->fromIncludedPosition();

        $filter->filterQuery()($builder->reveal());
    }

    /**
     * @test
     */
    public function it_raise_exception_when_position_is_invalid(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Position must be greater than 0, current is -1");

        $builder = $this->prophesize(Builder::class);
        $scope = new PgsqlProjectionQueryScope();
        $filter = $scope->fromIncludedPosition();
        $filter->setCurrentPosition(-1);

        $filter->filterQuery()($builder->reveal());
    }
}
