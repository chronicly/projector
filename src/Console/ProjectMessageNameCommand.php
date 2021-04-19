<?php
declare(strict_types=1);

namespace Chronhub\Projector\Console;

use Chronhub\Contracts\Aggregate\AggregateChanged;
use Chronhub\Contracts\Messaging\MessageHeader;
use Chronhub\Foundation\Support\Facades\AliasMessage;
use Closure;

final class ProjectMessageNameCommand extends AbstractPersistentProjectionCommand
{
    protected const ALIAS_MESSAGE = true;

    protected $signature = 'projector:message_name {--projector=default} {--signal=1} {--alias=1}';

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
        $asAlias = $this->isMessageNameMustBeAliased();

        return function (AggregateChanged $event) use ($asAlias): void {
            $messageName = $event->header(MessageHeader::EVENT_TYPE);

            if ($asAlias) {
                $messageName = AliasMessage::typeToAlias($messageName);
            }

            $this->linkTo('$mn-' . $messageName, $event);
        };
    }

    private function isMessageNameMustBeAliased(): bool
    {
        if ($this->hasOption('alias')) {
            return 1 === (int)$this->option('alias');
        }

        return self::ALIAS_MESSAGE;
    }
}
