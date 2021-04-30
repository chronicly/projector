<?php
declare(strict_types=1);

namespace Chronhub\Projector\Support\Console;

use Chronhub\Contracts\Projecting\ProjectorManager;
use Chronhub\Projector\Exception\InvalidArgumentException;
use Chronhub\Projector\Support\Facade\Project;
use Illuminate\Console\Command;

final class ReadProjectionCommand extends Command
{
    protected $signature = 'projector:find
                                {stream : stream name}
                                {field : available field (state position status)}
                                {--projector=default}';

    protected $description = 'Fetch status, stream position or state by projection stream name';

    private ProjectorManager $projector;

    public function handle(): void
    {
        $projectorId = $this->option('projector') ?? 'default';
        $this->projector = Project::create($projectorId);

        [$streamName, $fieldName] = $this->determineArguments();

        $result = $this->fetchProjectionByField($streamName, $fieldName);
        $result = empty($result) ? 'No result' : json_encode($result);

        $this->info("$fieldName for stream $streamName is $result");
    }

    private function fetchProjectionByField(string $streamName, string $fieldName): array
    {
        return match ($fieldName) {
            'state' => $this->projector->stateOf($streamName),
            'position' => $this->projector->streamPositionsOf($streamName),
            'status' => [$this->projector->statusOf($streamName)],
            default => throw new InvalidArgumentException("Invalid field name $fieldName"),
        };
    }

    private function determineArguments(): array
    {
        return [$this->argument('stream'), $this->argument('field')];
    }
}
