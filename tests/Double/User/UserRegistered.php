<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Double\User;

use Chronhub\Contracts\Aggregate\AggregateId;
use Chronhub\Foundation\Aggregate\AggregateChanged;

final class UserRegistered extends AggregateChanged
{
    public static function withData(AggregateId $aggregateId, string $name): self
    {
        return self::occur($aggregateId->toString(), [
            'name' => $name
        ]);
    }
}
