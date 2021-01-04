<?php
declare(strict_types=1);

namespace Chronhub\Projector\Model;

use Chronhub\Contracts\Model\ProjectionModel;
use Chronhub\Contracts\Model\ProjectionProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

final class Projection extends Model implements ProjectionModel, ProjectionProvider
{
    public $timestamps = false;
    public $table = self::TABLE;
    protected $fillable = ['name', 'position', 'state', 'locked_until'];
    protected $primaryKey = 'no';

    public function createProjection(string $name, string $status): bool
    {
        $projection = $this->newInstance();

        $projection['name'] = $name;
        $projection['status'] = $status;
        $projection['position'] = '{}';
        $projection['state'] = '{}';
        $projection['locked_until'] = null;

        return $projection->save();
    }

    public function updateProjection(string $name, array $data): bool
    {
        $result = $this->newInstance()->newQuery()
            ->where('name', $name)
            ->update($data);

        return 1 === $result;
    }

    public function deleteByName(string $name): bool
    {
        $result = (int)$this->newInstance()->newQuery()
            ->where('name', $name)
            ->delete();

        return 1 === $result;
    }

    public function projectionExists(string $name): bool
    {
        try {
            return $this->newInstance()->newQuery()
                ->where('name', $name)
                ->exists();
        } catch (QueryException) {
            return false;
        }
    }

    public function findByName(string $name): ?ProjectionModel
    {
        /** @var ProjectionModel $projection */
        $projection = $this->newInstance()->newQuery()
            ->where('name', $name)
            ->first();

        return $projection;
    }

    public function findByNames(string ...$names): array
    {
        return $this->newInstance()->newQuery()
            ->whereIn('name', $names)
            ->orderBy('name')
            ->pluck('name')
            ->toArray();
    }

    public function acquireLock(string $name, string $status, string $lockedUntil, string $now): bool
    {
        $result = $this->newInstance()->newQuery()
            ->where('name', $name)
            ->where(static function (Builder $query) use ($now) {
                $query->whereRaw('locked_until IS NULL OR locked_until < ?', [$now]);
            })->update([
                'status' => $status,
                'locked_until' => $lockedUntil
            ]);

        return 1 === $result;
    }

    public function name(): string
    {
        return $this['name'];
    }

    public function position(): string
    {
        return $this['position'];
    }

    public function state(): string
    {
        return $this['state'];
    }

    public function status(): string
    {
        return $this['status'];
    }

    public function lockedUntil(): ?string
    {
        return $this['locked_until'];
    }
}
