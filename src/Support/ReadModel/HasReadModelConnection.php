<?php
declare(strict_types=1);

namespace Chronhub\Projector\Support\ReadModel;

use Illuminate\Database\ConnectionInterface;
use Throwable;

trait HasReadModelConnection
{
    protected ConnectionInterface $connection;
    protected bool $isTransactionDisabled;

    public function initialize(): void
    {
        $this->connection->getSchemaBuilder()->create($this->tableName(), $this->up());
    }

    public function isInitialized(): bool
    {
        return $this->connection->getSchemaBuilder()->hasTable($this->tableName());
    }

    public function reset(): void
    {
        $resetReadModel = function (): void {
            $schema = $this->connection->getSchemaBuilder();

            $schema->disableForeignKeyConstraints();

            $this->connection->table($this->tableName())->truncate();

            $schema->enableForeignKeyConstraints();
        };

        $this->transactional($resetReadModel);
    }

    public function down(): void
    {
        $dropReadModel = function (): void {
            $schema = $this->connection->getSchemaBuilder();

            $schema->disableForeignKeyConstraints();

            $schema->drop($this->tableName());

            $schema->enableForeignKeyConstraints();
        };

        $this->transactional($dropReadModel);
    }

    /**
     * @param callable $process
     * @throws Throwable
     */
    protected function transactional(callable $process): void
    {
        if (!$this->isTransactionDisabled()) {
            $this->connection->beginTransaction();
        }

        try {
            $process($this);
        } catch (Throwable $exception) {
            if (!$this->isTransactionDisabled()) {
                $this->connection->rollBack();
            }

            throw $exception;
        }

        if (!$this->isTransactionDisabled()) {
            $this->connection->commit();
        }
    }

    protected function isTransactionDisabled(): bool
    {
        return $this->isTransactionDisabled;
    }

    abstract protected function up(): callable;

    abstract protected function tableName(): string;
}
