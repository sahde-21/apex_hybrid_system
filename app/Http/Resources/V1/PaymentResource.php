<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\V1\Concerns\FormatsApiValues;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Payment */
class PaymentResource extends JsonResource
{
    use FormatsApiValues;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Optional totals/currency are read via getAttribute so missing columns
        // remain null in the JSON payload without inventing model @property docs.
        $currency = $this->resource->getAttribute('currency_code');

        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'contact_id' => $this->contact_id,
            'payment_date' => $this->dateOnly($this->payment_date),
            'invoice_id' => $this->invoice_id,
            'bill_id' => $this->bill_id,
            'amount' => $this->money($this->amount),
            'type' => $this->enumValue($this->type),
            'payment_method' => $this->payment_method,
            'posted_at' => $this->isoDate($this->posted_at),
            'status' => $this->enumValue($this->status),
            'subtotal_amount' => $this->money($this->resource->getAttribute('subtotal_amount'), is_string($currency) ? $currency : null),
            'discount_amount' => $this->money($this->resource->getAttribute('discount_amount'), is_string($currency) ? $currency : null),
            'tax_amount' => $this->money($this->resource->getAttribute('tax_amount'), is_string($currency) ? $currency : null),
            'total_amount' => $this->money($this->resource->getAttribute('total_amount'), is_string($currency) ? $currency : null),
            'currency_code' => $currency,
            'notes' => $this->notes,
            'contact' => $this->whenLoaded('contact', fn () => new ContactResource($this->contact)),
            'lines' => DocumentLineResource::collection($this->whenLoaded('lines')),
            'created_at' => $this->isoDate($this->created_at),
            'updated_at' => $this->isoDate($this->updated_at),
        ];
    }
}
