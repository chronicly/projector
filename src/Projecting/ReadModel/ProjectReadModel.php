<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting\ReadModel;

use Chronhub\Contracts\Chronicling\Chronicler;
use Chronhub\Contracts\Messaging\MessageAlias;
use Chronhub\Contracts\Projecting\ContextualEventHandler;
use Chronhub\Contracts\Projecting\PersistentProjectorContext;
use Chronhub\Contracts\Projecting\PersistentReadModelProjector;
use Chronhub\Contracts\Projecting\ProjectorFactory;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Contracts\Projecting\ReadModel;
use Chronhub\Projector\Projecting\Concern\HasPersistentProjector;
use Chronhub\Projector\Projecting\Concern\HasProjectorFactory;
use Chronhub\Projector\Projecting\Factory\ContextBuilder;
use JetBrains\PhpStorm\Pure;

final class ProjectReadModel implements PersistentReadModelProjector, ProjectorFactory
{
    use HasPersistentProjector, HasProjectorFactory;

    #[Pure]
    public function __construct(protected PersistentProjectorContext $context,
                                protected ProjectorRepository $repository,
                                protected Chronicler $chronicler,
                                protected MessageAlias $messageAlias,
                                protected string $streamName,
                                private ReadModel $readModel)
    {
        $this->builder = new ContextBuilder();
    }

    protected function createContextualEventHandler(): ContextualEventHandler
    {
        $currentStreamName = $this->context->currentStreamName();

        return new ReadModelEventHandler($this, $currentStreamName);
    }

    public function readModel(): ReadModel
    {
        return $this->readModel;
    }
}
