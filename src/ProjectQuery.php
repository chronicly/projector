<?php
declare(strict_types=1);

namespace Chronhub\Projector;

use Chronhub\Contracts\Chronicling\Chronicler;
use Chronhub\Contracts\Messaging\MessageAlias;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorFactory;
use Chronhub\Contracts\Projecting\QueryProjector;
use Chronhub\Projector\Concern\HasProjectorFactory;
use Chronhub\Projector\Context\ContextualQuery;
use Chronhub\Projector\Factory\RunnerController;
use function is_array;

final class ProjectQuery implements QueryProjector, ProjectorFactory
{
    use HasProjectorFactory;

    public function __construct(protected ProjectorContext $context,
                                private Chronicler $chronicler,
                                private MessageAlias $messageAlias)
    {
    }

    public function run(bool $inBackground): void
    {
        $this->context->withRunner(
            new RunnerController($inBackground, false)
        );

        $this->context->bindContextualEventHandler(new ContextualQuery($this, $this->context));

        $runner = new QueryRunner($this->chronicler);

        $runner($this->context);
    }

    public function stop(): void
    {
        $this->context->runner()->stop(true);
    }

    public function reset(): void
    {
        $this->context->position()->reset();

        $state = $this->context->resetStateWithInitialize();

        if (!is_array($state)) {
            $this->context->state()->resetState();
        }
    }

    public function getState(): array
    {
        return $this->context->state()->getState();
    }
}
