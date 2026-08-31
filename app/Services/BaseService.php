<?php

namespace App\Services;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @template TModel of Model
 */
abstract class BaseService
{
    /**
     * @param  RepositoryInterface<TModel>  $repository
     */
    public function __construct(
        protected RepositoryInterface $repository,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public function store(array $data): Model
    {
        return $this->repository->create($data);
    }

    /**
     * @param  TModel  $model
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public function update(Model $model, array $data): Model
    {
        return $this->repository->update($model, $data);
    }

    /**
     * @param  TModel  $model
     */
    public function destroy(Model $model): bool
    {
        return $this->repository->delete($model);
    }

    /**
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }
}
