<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests;

use Prophecy\PhpUnit\ProphecyTrait;

abstract class TestCaseWithProphecy extends TestCase
{
    use ProphecyTrait;
}
