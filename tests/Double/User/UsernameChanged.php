<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Double\User;


use Chronhub\Foundation\Aggregate\AggregateChanged;

final class UsernameChanged extends AggregateChanged
{
    public static function withName(UserId $userId, string $newName, string $oldName): self
    {
        return self::occur($userId->toString(), [
            'new_name' => $newName,
            'old_name' => $oldName
        ]);
    }
}
