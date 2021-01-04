<?php
declare(strict_types=1);

namespace Chronhub\Projector\Exception;

use Chronhub\Contracts\Exception\ProjectingException;
use Chronhub\Foundation\Exception\RuntimeException as BaseException;

class RuntimeException extends BaseException  implements ProjectingException
{
    //
}
