<?php

namespace App\Repository\v1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * 基础 Repository
 * - 仅做 DB 查询，无业务
 * - 子类通过 setModel() 指定关联 Model
 */
abstract class BaseRepository
{
    protected Model $model;

    public function __construct(?Model $model = null)
    {
        if ($model) {
            $this->model = $model;
        }
    }

    /**
     * 子类必须实现
     */
    abstract protected function model(): string;

    public function newQuery(): Builder
    {
        $this->model = new ($this->model());
        return $this->model->newQuery();
    }

    public function find(int $id): ?Model
    {
        return $this->newQuery()->find($id);
    }

    public function findOrFail(int $id): Model
    {
        return $this->newQuery()->findOrFail($id);
    }

    public function findBy(string $field, $value): ?Model
    {
        return $this->newQuery()->where($field, $value)->first();
    }

    public function create(array $data): Model
    {
        return $this->newQuery()->create($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->newQuery()->where('id', $id)->update($data) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->newQuery()->where('id', $id)->delete() > 0;
    }
}
