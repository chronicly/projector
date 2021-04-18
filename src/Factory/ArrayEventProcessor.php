<?php
declare(strict_types=1);

namespace Chronhub\Projector\Factory;

use Chronhub\Contracts\Messaging\Message;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Foundation\Support\Facades\AliasMessage;

final class ArrayEventProcessor extends AbstractEventProcessor
{
    public function __construct(private array $eventHandlers)
    {
    }

    public function __invoke(ProjectorContext $context, Message $message, int $key, ?ProjectorRepository $repository): bool
    {
        if (!$this->preProcess($context, $message, $key, $repository)) {
            return false;
        }

        if (!$messageHandler = $this->determineEventHandler($message)) {
            $repository and $this->persistOnReachedCounter($context, $repository);

            return !$context->runner()->isStopped();
        }

        $state = $messageHandler($message->eventWithHeaders(), $context->state()->getState());

        return $this->afterProcess($context, $state, $repository);
    }

    private function determineEventHandler(Message $message): ?callable
    {
        $eventAlias = AliasMessage::instanceToAlias($message);

        return $this->eventHandlers[$eventAlias] ?? null;
    }
}
