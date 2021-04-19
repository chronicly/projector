<?php
declare(strict_types=1);

namespace Chronhub\Projector\Console;

use Chronhub\Contracts\Aggregate\AggregateChanged;
use Chronhub\Contracts\Messaging\MessageHeader;
use Closure;

final class ProjectMessageNameCommand extends AbstractPersistentProjectionCommand
{
    protected $signature = 'projector:message_name {--projector=default} {--signal=1}';

    public function handle(): void
    {
        $this->withProjection('$by_message_name');

        $this->projector
            ->fromAll()
            ->whenAny($this->eventHandler())
            ->run(true);
    }

    private function eventHandler(): Closure
    {
        return function (AggregateChanged $event): void {
            $messageName = $event->header(MessageHeader::EVENT_TYPE);

            $this->linkTo('$mn-' . $messageName, $event);
        };
    }
}
