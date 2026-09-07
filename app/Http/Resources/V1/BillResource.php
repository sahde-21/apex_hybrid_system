<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\V1\Concerns\FormatsApiValues;
use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Bill */
class BillResource extends JsonResource
{
    use FormatsApiValues;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'contact_id' => $this->contact_id,
            'bill_date' => $this->dateOnly($this->bill_date),
            'due_date' => $this->dateOnly($this->due_date),
            'purchase_order_id' => $this->purchase_order_id,
            'paid_amount' => $this->money($this->paid_amount, $this->currency_code),
            'issued_at' => $this->isoDate($this->issued_at),
            'status' => $this->enumValue($this->status),
            'subtotal_amount' => $this->money($this->subtotal_amount, $this->currency_code),
            'discount_amount' => $this->money($this->discount_amount, $this->currency_code),
            'tax_amount' => $this->money($this->tax_amount, $this->currency_code),
            'total_amount' => $this->money($this->total_amount, $this->currency_code),
            'currency_code' => $this->currency_code,
            'notes' => $this->notes,
            'contact' => $this->whenLoaded('contact', fn () => new ContactResource($this->contact)),
            'lines' => DocumentLineResource::collection($this->whenLoaded('lines')),
            'created_at' => $this->isoDate($this->created_at),
            'updated_at' => $this->isoDate($this->updated_at),
        ];
    }
}
