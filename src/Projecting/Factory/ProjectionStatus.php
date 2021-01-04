<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting\Factory;

use Chronhub\Contracts\Projecting\ProjectionStatus as Status;
use JetBrains\PhpStorm\Pure;
use MabeEnum\Enum;

/**
 * @method static ProjectionStatus RUNNING()
 * @method static ProjectionStatus STOPPING()
 * @method static ProjectionStatus DELETING()
 * @method static ProjectionStatus DELETING_EMITTED_EVENTS()
 * @method static ProjectionStatus RESETTING()
 * @method static ProjectionStatus IDLE()
 */
final class ProjectionStatus extends Enum implements Status
{
    #[Pure]
    public function ofValue(): string
    {
        return (string)$this->getValue();
    }
}
