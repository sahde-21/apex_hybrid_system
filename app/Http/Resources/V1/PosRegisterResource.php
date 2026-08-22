<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\V1\Concerns\FormatsApiValues;
use App\Models\PosRegister;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PosRegister */
class PosRegisterResource extends JsonResource
{
    use FormatsApiValues;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'warehouse_id' => $this->warehouse_id,
            'branch_id' => $this->branch_id,
            'is_active' => $this->is_active,
            'cash_drawer_enabled' => $this->cash_drawer_enabled,
            'notes' => $this->notes,
            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->warehouse === null ? null : [
                'id' => $this->warehouse->id,
                'name' => $this->warehouse->name,
                'code' => $this->warehouse->code,
            ]),
            'branch' => $this->whenLoaded('branch', fn () => $this->branch === null ? null : [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
                'code' => $this->branch->code,
            ]),
            'created_at' => $this->isoDate($this->created_at),
            'updated_at' => $this->isoDate($this->updated_at),
        ];
    }
}
