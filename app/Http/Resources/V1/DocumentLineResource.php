<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\V1\Concerns\FormatsApiValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentLineResource extends JsonResource
{
    use FormatsApiValues;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'line_number' => $this->line_number,
            'product_id' => $this->product_id,
            'description' => $this->description,
            'quantity' => (string) $this->quantity,
            'unit_price' => $this->money($this->unit_price),
            'discount_amount' => $this->money($this->discount_amount),
            'tax_amount' => $this->money($this->tax_amount),
            'line_total' => $this->money($this->line_total),
            'product' => $this->whenLoaded('product', fn () => new ProductResource($this->product)),
        ];
    }
}
