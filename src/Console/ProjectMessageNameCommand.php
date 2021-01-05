<?php
declare(strict_types=1);

namespace Chronhub\Projector\Console;

use Chronhub\Contracts\Aggregate\AggregateChanged;
use Chronhub\Contracts\Messaging\MessageHeader;

final class ProjectMessageNameCommand extends AbstractPersistentProjectionCommand
{
    protected $signature = 'projector:message_name {--projector=default} {--signal=1}';

    public function handle(): void
    {
        $projection = $this->withProjection('$by_message_name');

        $projection
            ->fromAll()
            ->whenAny(function (AggregateChanged $event): void {
                $messageName = $event->header(MessageHeader::EVENT_TYPE);

                $this->linkTo('$mn-' . $messageName, $event);
            })->run(true);
    }
}
