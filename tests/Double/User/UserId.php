<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Double\User;

use Chronhub\Contracts\Aggregate\AggregateId;
use Chronhub\Foundation\Aggregate\HasAggregateUuid;

final class UserId implements AggregateId
{
    use HasAggregateUuid;
}
