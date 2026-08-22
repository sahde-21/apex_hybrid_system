<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\V1\Concerns\FormatsApiValues;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Account */
class AccountResource extends JsonResource
{
    use FormatsApiValues;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'parent_id' => $this->parent_id,
            'type' => $this->enumValue($this->type),
            'normal_balance' => $this->enumValue($this->normal_balance),
            'currency_code' => $this->currency_code,
            'branch_id' => $this->branch_id,
            'is_active' => $this->is_active,
            'is_system' => $this->is_system,
            'allow_manual_entry' => $this->allow_manual_entry,
            'system_key' => $this->system_key,
            'description' => $this->description,
            'opening_balance' => $this->money($this->opening_balance, $this->currency_code),
            'parent' => $this->whenLoaded('parent', fn () => $this->parent === null ? null : [
                'id' => $this->parent->id,
                'code' => $this->parent->code,
                'name' => $this->parent->name,
            ]),
            'children' => $this->whenLoaded('children', fn () => $this->children
                ->map(fn (Account $child) => [
                    'id' => $child->id,
                    'code' => $child->code,
                    'name' => $child->name,
                ])
                ->values()
                ->all()),
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
