<?php

namespace App\Support;

use App\Models\PortalSupplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait ScopesToSupplierContact
{
    protected function portalSupplier(): ?PortalSupplier
    {
        /** @var PortalSupplier|null $supplier */
        $supplier = auth('supplier')->user();

        return $supplier;
    }

    protected function supplierContactId(): ?int
    {
        return $this->portalSupplier()?->contact_id;
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function scopeOwned(Builder $query, string $column = 'contact_id'): Builder
    {
        $contactId = $this->supplierContactId();

        abort_if($contactId === null, 403);

        return $query->where($column, $contactId);
    }

    protected function assertOwns(?Model $model, string $column = 'contact_id'): void
    {
        abort_if($model === null, 404);

        $contactId = $this->supplierContactId();

        abort_if($contactId === null, 403);
        abort_unless((int) $model->getAttribute($column) === (int) $contactId, 403);
    }
}
