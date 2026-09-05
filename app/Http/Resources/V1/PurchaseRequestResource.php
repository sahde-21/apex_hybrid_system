<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\V1\Concerns\FormatsApiValues;
use App\Models\PurchaseRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PurchaseRequest */
class PurchaseRequestResource extends JsonResource
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
            // Legacy API fields — not modeled on PurchaseRequest; remain null when absent.
            'contact_id' => $this->resource->getAttribute('contact_id'),
            'request_date' => $this->dateOnly($this->request_date),
            'needed_by' => $this->dateOnly($this->needed_by),
            'requester_id' => $this->requester_id,
            'department' => $this->department,
            'converted_rfq_id' => $this->converted_rfq_id,
            'status' => $this->enumValue($this->status),
            'subtotal_amount' => $this->money($this->subtotal_amount, $this->currency_code),
            'discount_amount' => $this->money($this->discount_amount, $this->currency_code),
            'tax_amount' => $this->money($this->tax_amount, $this->currency_code),
            'total_amount' => $this->money($this->total_amount, $this->currency_code),
            'currency_code' => $this->currency_code,
            'notes' => $this->notes,
            'contact' => $this->whenLoaded('contact', function () {
                return new ContactResource($this->resource->getAttribute('contact'));
            }),
            'lines' => DocumentLineResource::collection($this->whenLoaded('lines')),
            'created_at' => $this->isoDate($this->created_at),
            'updated_at' => $this->isoDate($this->updated_at),
        ];
    }
}
