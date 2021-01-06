<?php
declare(strict_types=1);

namespace Chronhub\Projector;

use Chronhub\Contracts\Chronicling\Chronicler;
use Chronhub\Contracts\Messaging\MessageAlias;
use Chronhub\Contracts\Projecting\ContextualEventHandler;
use Chronhub\Contracts\Projecting\PersistentReadModelProjector;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorFactory;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Contracts\Projecting\ReadModel;
use Chronhub\Projector\Concern\HasPersistentProjector;
use Chronhub\Projector\Concern\HasProjectorFactory;
use Chronhub\Projector\Context\ContextualReadModel;
use JetBrains\PhpStorm\Pure;

final class ProjectReadModel implements PersistentReadModelProjector, ProjectorFactory
{
    use HasPersistentProjector, HasProjectorFactory;

    #[Pure]
    public function __construct(protected ProjectorContext $context,
                                protected ProjectorRepository $repository,
                                protected Chronicler $chronicler,
                                protected MessageAlias $messageAlias,
                                protected string $streamName,
                                private ReadModel $readModel)
    {
    }

    protected function createContextualEventHandler(): ContextualEventHandler
    {
        $currentStreamName = $this->context->currentStreamName();

        return new ContextualReadModel($this, $currentStreamName);
    }

    public function readModel(): ReadModel
    {
        return $this->readModel;
    }
}
