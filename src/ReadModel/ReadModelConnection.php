<?php
declare(strict_types=1);

namespace Chronhub\Projector\ReadModel;

use Illuminate\Database\ConnectionInterface;

abstract class ReadModelConnection extends AbstractReadModel
{
    use HasConnectionOperation, HasReadModelConnection;

    public function __construct(protected ConnectionInterface $connection,
                                protected bool $isTransactionDisabled = false)
    {
    }
}
