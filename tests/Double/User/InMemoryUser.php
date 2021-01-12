<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Double\User;

final class InMemoryUser
{
    public function __construct(private string $userId, private string $name)
    {
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
