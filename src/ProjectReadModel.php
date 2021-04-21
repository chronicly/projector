<?php
declare(strict_types=1);

namespace Chronhub\Projector;

use Chronhub\Contracts\Chronicling\Chronicler;
use Chronhub\Contracts\Projecting\ContextualEventHandler;
use Chronhub\Contracts\Projecting\PersistentReadModelProjector;
use Chronhub\Contracts\Projecting\ProjectorFactory;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Contracts\Projecting\ReadModel;
use Chronhub\Projector\Concern\HasPersistentProjector;
use Chronhub\Projector\Concern\HasProjectorFactory;
use Chronhub\Projector\Context\ContextualReadModel;
use Chronhub\Projector\Context\ProjectorContext;
use JetBrains\PhpStorm\Pure;

final class ProjectReadModel implements PersistentReadModelProjector, ProjectorFactory
{
    use HasPersistentProjector, HasProjectorFactory;

    public function __construct(protected ProjectorContext $context,
                                protected ProjectorRepository $repository,
                                protected Chronicler $chronicler,
                                protected string $streamName,
                                private ReadModel $readModel)
    {
    }

    #[Pure]
    protected function createContextualProjector(): ContextualEventHandler
    {
        return new ContextualReadModel($this, $this->context);
    }

    public function readModel(): ReadModel
    {
        return $this->readModel;
    }
}
