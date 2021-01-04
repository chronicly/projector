<?php
declare(strict_types=1);

namespace Chronhub\Projector\ReadModel;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;

trait HasConnectionOperation
{
    protected ConnectionInterface $connection;

    protected function insert(array $data): void
    {
        $this->queryBuilder()->insert($data);
    }

    protected function update(string $id, array $data): void
    {
        $this->queryBuilder()->where($this->getKey(), $id)->update($data);
    }

    /**
     * @param string    $id
     * @param string    $column
     * @param int|float $value
     * @param array     $extra
     */
    protected function increment(string $id, string $column, float|int $value, array $extra = []): void
    {
        $this->queryBuilder()
            ->where($this->getKey(), $id)
            ->increment($column, abs($value), $extra);
    }

    /**
     * @param string    $id
     * @param string    $column
     * @param int|float $value
     * @param array     $extra
     */
    protected function decrement(string $id, string $column, int|float $value, array $extra = []): void
    {
        $this->queryBuilder()
            ->where($this->getKey(), $id)
            ->decrement($column, abs($value), $extra);
    }

    protected function queryBuilder(): Builder
    {
        return $this->connection->table($this->tableName());
    }

    protected function getKey(): string
    {
        return 'id';
    }

    abstract protected function tableName(): string;
}
