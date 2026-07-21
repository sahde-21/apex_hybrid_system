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

    public function toArray(Request $request): array
    {
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
