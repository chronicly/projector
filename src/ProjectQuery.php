<?php
declare(strict_types=1);

namespace Chronhub\Projector;

use Chronhub\Contracts\Chronicling\Chronicler;
use Chronhub\Contracts\Messaging\MessageAlias;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorFactory;
use Chronhub\Contracts\Projecting\QueryProjector;
use Chronhub\Projector\Context\ContextualQuery;
use Chronhub\Projector\Exception\InvalidArgumentException;
use Chronhub\Projector\Concern\HasProjectorFactory;
use Closure;
use JetBrains\PhpStorm\Pure;
use function is_array;

final class ProjectQuery implements QueryProjector, ProjectorFactory
{
    use HasProjectorFactory;

    #[Pure]
    public function __construct(protected ProjectorContext $context,
                                private Chronicler $chronicler,
                                private MessageAlias $messageAlias)
    {
    }

    public function run(bool $keepRunning = true): void
    {
        if ($keepRunning) {
            throw new InvalidArgumentException("Query projection can not run in background");
        }

        $currentStreamName = $this->context->currentStreamName();

        $this->context->bindContextualEventHandler(new ContextualQuery($this, $currentStreamName));

        $runner = new QueryRunner($this, $this->chronicler, $this->messageAlias);

        $runner($this->context);
    }

    public function stop(): void
    {
        $this->context->stopProjection(true);
    }

    public function reset(): void
    {
        $this->context->position()->reset();

        $callback = $this->context->initCallback();

        if ($callback instanceof Closure) {
            $state = $callback();

            if (is_array($state)) {
                $this->context->state()->setState($state);

                return;
            }
        }

        $this->context->state()->resetState();
    }

    public function getState(): array
    {
        return $this->context->state()->getState();
    }
}
