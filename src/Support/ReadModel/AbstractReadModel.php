<?php
declare(strict_types=1);

namespace Chronhub\Projector\Support\ReadModel;

use Chronhub\Contracts\Projecting\ReadModel;

abstract class AbstractReadModel implements ReadModel
{
    use HasReadModelOperation;
}
