<?php
declare(strict_types=1);

namespace Chronhub\Projector\Context;

use Chronhub\Contracts\Projecting\PersistentReadModelProjector;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ReadModel;
use Chronhub\Contracts\Projecting\ReadModelEventHandler as ContextualEventHandler;

final class ContextualReadModel implements ContextualEventHandler
{
    public function __construct(private PersistentReadModelProjector $projector,
                                private ProjectorContext $context)
    {
    }

    public function stop(): void
    {
        $this->projector->stop();
    }

    public function readModel(): ReadModel
    {
        return $this->projector->readModel();
    }

    public function streamName(): ?string
    {
        return $this->context->currentStreamName();
    }
}
