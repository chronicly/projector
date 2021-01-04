<?php
declare(strict_types=1);

namespace Chronhub\Projector\Exception;

use Chronhub\Contracts\Exception\ProjectingException;
use Chronhub\Foundation\Exception\InvalidArgumentException as BaseException;

final class InvalidArgumentException extends BaseException implements ProjectingException
{
    //
}
