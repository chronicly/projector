<?php
declare(strict_types=1);

namespace Chronhub\Projector\Support\Scope;

use Chronhub\Chronicler\Driver\InMemory\InMemoryQueryScope;
use Chronhub\Contracts\Messaging\Message;
use Chronhub\Contracts\Messaging\MessageHeader;
use Chronhub\Contracts\Query\ProjectionQueryFilter;
use Chronhub\Contracts\Query\ProjectionQueryScope;
use Chronhub\Projector\Exception\RuntimeException;

class InMemoryProjectionQueryScope extends InMemoryQueryScope implements ProjectionQueryScope
{
    public function fromIncludedPosition(): ProjectionQueryFilter
    {
        return new class() implements ProjectionQueryFilter {
            private int $currentPosition = 0;

            public function setCurrentPosition(int $position): void
            {
                $this->currentPosition = $position;
            }

            public function filterQuery(): callable
            {
                $position = $this->currentPosition;

                if ($position <= 0) {
                    throw new RuntimeException("Position must be greater than 0, current is $position");
                }

                return function (Message $message) use ($position): ?Message {
                    return $message->header(MessageHeader::INTERNAL_POSITION) >= $position
                        ? $message : null;
                };
            }
        };
    }
}
