<?php
declare(strict_types=1);

namespace Chronhub\Projector\Support\Option;

trait HasArrayableProjectorOption
{
    public function toArray(): array
    {
        return [
            self::OPTION_PCNTL_DISPATCH => $this->dispatchSignal(),
            self::OPTION_STREAM_CACHE_SIZE => $this->streamCacheSize(),
            self::OPTION_LOCK_TIMEOUT_MS => $this->lockTimoutMs(),
            self::OPTION_SLEEP => $this->sleep(),
            self::OPTION_UPDATE_LOCK_THRESHOLD => $this->updateLockThreshold(),
            self::OPTION_PERSIST_BLOCK_SIZE => $this->persistBlockSize(),
            self::OPTION_RETRIES_MS => $this->retriesMs(),
            self::OPTION_DETECTION_WINDOWS => $this->detectionWindows(),
        ];
    }
}
