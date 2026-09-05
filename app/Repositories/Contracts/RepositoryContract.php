<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface RepositoryContract
{
    /**
     * @return Builder<Model>
     */
    public function query(): Builder;

    /**
     * @return Collection<int, Model>
     */
    public function all(): Collection;

    public function find(int $id): ?Model;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model;

    public function delete(Model $model): bool;

    /**
     * @return LengthAwarePaginator<int, Model>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;
}
