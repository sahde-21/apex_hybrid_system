<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\V1\Concerns\FormatsApiValues;
use App\Models\PosShift;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PosShift */
class PosShiftResource extends JsonResource
{
    use FormatsApiValues;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pos_register_id' => $this->pos_register_id,
            'user_id' => $this->user_id,
            'status' => $this->enumValue($this->status),
            'opened_at' => $this->isoDate($this->opened_at),
            'closed_at' => $this->isoDate($this->closed_at),
            'opening_amount' => $this->money($this->opening_float),
            'closing_amount' => $this->closing_cash === null ? null : $this->money($this->closing_cash),
            'opening_notes' => $this->opening_notes,
            'closing_notes' => $this->closing_notes,
            'register' => $this->whenLoaded('register', fn () => $this->register === null ? null : [
                'id' => $this->register->id,
                'name' => $this->register->name,
                'code' => $this->register->code,
            ]),
            'user' => $this->whenLoaded('user', fn () => $this->user === null ? null : [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'sales' => $this->whenLoaded('sales', fn () => $this->sales
                ->map(fn ($sale) => [
                    'id' => $sale->id,
                    'total_amount' => $sale->total_amount,
                    'status' => $sale->status,
                ])
                ->values()
                ->all()),
            'created_at' => $this->isoDate($this->created_at),
            'updated_at' => $this->isoDate($this->updated_at),
        ];
    }
}
