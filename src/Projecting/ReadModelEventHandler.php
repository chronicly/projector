<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting;

use Chronhub\Contracts\Projecting\PersistentReadModelProjector;
use Chronhub\Contracts\Projecting\ReadModel;
use Chronhub\Contracts\Projecting\ReadModelContextualEventHandler;

final class ReadModelEventHandler implements ReadModelContextualEventHandler
{
    private PersistentReadModelProjector $projector;
    private ?string $streamName;

    public function __construct(PersistentReadModelProjector $projector,
                                ?string &$streamName)
    {
        $this->projector = $projector;
        $this->streamName = &$streamName;
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
