<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting;

use Chronhub\Contracts\Chronicling\Chronicler;
use Chronhub\Contracts\Messaging\MessageAlias;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorFactory;
use Chronhub\Contracts\Projecting\QueryProjector;
use Chronhub\Projector\Exception\InvalidArgumentException;
use Chronhub\Projector\Projecting\Concern\HasProjectorFactory;
use Chronhub\Projector\Projecting\Factory\ContextBuilder;
use Chronhub\Projector\Projecting\ProjectorRunner;
use Closure;
use JetBrains\PhpStorm\Pure;
use function is_array;

final class ProjectQuery implements QueryProjector, ProjectorFactory
{
    use HasProjectorFactory;

    protected ContextBuilder $builder;

    #[Pure]
    public function __construct(private ProjectorContext $context,
                                private Chronicler $chronicler,
                                private MessageAlias $messageAlias)
    {
        $this->builder = new ContextBuilder();
    }

    public function run(bool $keepRunning = true): void
    {
        if ($keepRunning) {
            throw new InvalidArgumentException("Query projection can not run in background");
        }

        $currentStreamName = $this->context->currentStreamName();

        $this->context->setUp($this->builder, new QueryEventHandler($this, $currentStreamName));

        $processor = new ProjectorRunner($this, $this->chronicler, $this->messageAlias, null);

        $processor->process($this->context);
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
