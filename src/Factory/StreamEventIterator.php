<?php
declare(strict_types=1);

namespace Chronhub\Projector\Factory;

use Chronhub\Contracts\Messaging\Message;
use Chronhub\Contracts\Messaging\MessageHeader;
use Chronhub\Foundation\Exception\StreamNotFound;
use Chronhub\Projector\Exception\RuntimeException;
use Generator;
use Iterator;

final class StreamEventIterator implements Iterator
{
    private ?Message $currentMessage = null;
    private int $currentKey = 0;

    public function __construct(private Generator $eventStreams)
    {
        $this->next();
    }

    public function current(): ?Message
    {
        return $this->currentMessage;
    }

    public function next(): void
    {
        try {
            $this->currentMessage = $this->eventStreams->current();

            if ($this->currentMessage) {
                $position = (int)$this->currentMessage->header(MessageHeader::INTERNAL_POSITION);

                if ($position <= 0) {
                    throw new RuntimeException("Stream event position must be greater than 0, current is $position");
                }

                $this->currentKey = $position;
            } else {
                $this->resetProperties();
            }

            $this->eventStreams->next();
        } catch (StreamNotFound) {
            $this->resetProperties();
        }
    }

    public function key(): bool|int
    {
        if (null === $this->currentMessage || 0 === $this->currentKey) {
            return false;
        }

        return $this->currentKey;
    }

    public function valid(): bool
    {
        return null !== $this->currentMessage;
    }

    public function rewind(): void
    {
        //
    }

    private function resetProperties(): void
    {
        $this->currentKey = 0;
        $this->currentMessage = null;
    }
}
