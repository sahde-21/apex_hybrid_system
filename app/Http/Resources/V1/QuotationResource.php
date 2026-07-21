<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\V1\Concerns\FormatsApiValues;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/** @mixin Quotation */
class QuotationResource extends JsonResource
{
    use FormatsApiValues;

    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'contact_id' => $this->contact_id,
            'quotation_date' => $this->dateOnly($this->quotation_date),
            'valid_until' => $this->dateOnly($this->valid_until),
            'status' => $this->enumValue($this->status),
            'subtotal_amount' => $this->money($this->subtotal_amount, $this->currency_code),
            'discount_amount' => $this->money($this->discount_amount, $this->currency_code),
            'tax_amount' => $this->money($this->tax_amount, $this->currency_code),
            'total_amount' => $this->money($this->total_amount, $this->currency_code),
            'currency_code' => $this->currency_code,
            'notes' => $this->notes,
            'terms' => $this->terms,
            'converted_sale_order_id' => $this->converted_sale_order_id,
            'converted_at' => $this->isoDate($this->converted_at),
            'salesperson_id' => $this->salesperson_id,
            'contact' => $this->whenLoaded('contact', fn () => new ContactResource($this->contact)),
            'lines' => DocumentLineResource::collection($this->whenLoaded('lines')),
            'converted_sale_order' => $this->whenLoaded('convertedSaleOrder', fn () => [
                'id' => $this->convertedSaleOrder?->id,
                'reference_number' => $this->convertedSaleOrder?->reference_number,
            ]),
            'allowed_actions' => $user ? $this->allowedActions($user) : [],
            'created_at' => $this->isoDate($this->created_at),
            'updated_at' => $this->isoDate($this->updated_at),
        ];
    }

    private function allowedActions($user): array
    {
        $actions = [];
        foreach (['send', 'approve', 'reject', 'cancel', 'convert'] as $action) {
            if (Gate::forUser($user)->allows($action, $this->resource)) {
                $actions[] = $action === 'approve' ? 'accept' : $action;
            }
        }
        if ($user->can('quotations.create')) {
            $actions[] = 'duplicate';
        }
        if ($user->can('update', $this->resource) && $this->status->isEditable()) {
            $actions[] = 'update';
        }
        if ($user->can('delete', $this->resource)) {
            $actions[] = 'delete';
        }
        return array_values(array_unique($actions));
    }
}
