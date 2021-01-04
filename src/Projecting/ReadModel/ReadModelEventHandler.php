<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting\ReadModel;

use Chronhub\Contracts\Projecting\PersistentReadModelProjector;
use Chronhub\Contracts\Projecting\ReadModel;
use Chronhub\Contracts\Projecting\ReadModelContextualEventHandler;

final class ReadModelEventHandler implements ReadModelContextualEventHandler
{
    public function __construct(private PersistentReadModelProjector $projector,
                                private ?string &$streamName)
    {
    }

    public function stop(): void
    {
        $this->projector->stop();
    }

    public function streamName(): ?string
    {
        return $this->streamName;
    }

    public function readModel(): ReadModel
    {
        return $this->projector->readModel();
    }
}
