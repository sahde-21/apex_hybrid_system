<?php

namespace App\Support;

use App\Models\PortalCustomer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait ScopesToPortalContact
{
    protected function portalCustomer(): ?PortalCustomer
    {
        /** @var PortalCustomer|null $customer */
        $customer = auth('portal')->user();

        return $customer;
    }

    protected function portalContactId(): ?int
    {
        return $this->portalCustomer()?->contact_id;
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function scopeOwned(Builder $query, string $column = 'contact_id'): Builder
    {
        $contactId = $this->portalContactId();

        abort_if($contactId === null, 403);

        return $query->where($column, $contactId);
    }

    protected function assertOwns(?Model $model, string $column = 'contact_id'): void
    {
        abort_if($model === null, 404);

        $contactId = $this->portalContactId();

        abort_if($contactId === null, 403);
        abort_unless((int) $model->getAttribute($column) === (int) $contactId, 403);
    }
}
