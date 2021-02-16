<?php
declare(strict_types=1);

namespace Chronhub\Projector\Support;

use Chronhub\Contracts\Clock\Clock;
use Chronhub\Contracts\Messaging\Message;
use Chronhub\Contracts\Messaging\MessageHeader;
use function array_key_exists;
use function usleep;

trait HasGapDetector
{
    protected string $detectionWindows = 'PT1S';
    protected array $retriesMs;
    private int $retries = 0;

    protected function handleGapDetected(bool $gapDetected): void
    {
        if ($gapDetected) {
            usleep($this->retriesMs[$this->retries]);

            $this->retries++;

            $this->repository->persist();
        } else {
            $this->retries = 0;
        }
    }

    protected function hasGap(int $streamPosition, int $eventPosition, Message $message, Clock $clock): bool
    {
        if (empty($this->retriesMs)) {
            return false;
        }

        $now = $clock->pointInTime()->sub($this->detectionWindows);

        if ($now->after($message->header(MessageHeader::TIME_OF_RECORDING))) {
            return false;
        }

        return $streamPosition + 1 !== $eventPosition && array_key_exists($this->retries, $this->retriesMs);
    }
}
