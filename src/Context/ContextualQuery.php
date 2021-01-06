<?php
declare(strict_types=1);

namespace Chronhub\Projector\Context;

use Chronhub\Contracts\Projecting\ContextualEventHandler;
use Chronhub\Contracts\Projecting\QueryProjector;

final class ContextualQuery implements ContextualEventHandler
{
    /**
     * @var QueryProjector
     */
    private QueryProjector $query;
    private ?string $streamName;

    public function __construct(QueryProjector $query, ?string &$streamName)
    {
        $this->query = $query;
        $this->streamName = &$streamName;
    }

    public function stop(): void
    {
        $this->query->stop();
    }

    public function streamName(): ?string
    {
        return $this->streamName;
    }
}
