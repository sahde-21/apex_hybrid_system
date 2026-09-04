<?php

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;
use App\Support\Eloquent\ModelQueryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @template TModel of Model
 */
abstract class BaseRepository implements RepositoryInterface
{
    /** @var class-string<TModel> */
    protected string $model;

    /**
     * @return Builder<Model>
     */
    public function query(): Builder
    {
        return ModelQueryFactory::queryFor($this->model);
    }

    /**
     * @return Collection<int, Model>
     */
    public function all(): Collection
    {
        return $this->query()->get();
    }

    public function find(int $id): ?Model
    {
        return $this->query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        return $this->query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model
    {
        $model->update($data);
        $model->refresh();

        return $model;
    }

    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }

    /**
     * @return LengthAwarePaginator<int, Model>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()->latest()->paginate($perPage);
    }
}
