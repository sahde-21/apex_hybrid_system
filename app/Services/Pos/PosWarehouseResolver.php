<?php

namespace App\Services\Pos;

use App\Models\PosRegister;
use App\Models\PosShift;
use App\Models\Warehouse;
use InvalidArgumentException;

class PosWarehouseResolver
{
    public function resolveFromShift(PosShift $shift): Warehouse
    {
        $shift->loadMissing('register.warehouse');

        $register = $shift->register;

        if ($register === null) {
            throw new InvalidArgumentException(__('POS register not found for this shift.'));
        }

        return $this->resolveFromRegister($register);
    }

    public function resolveFromRegister(PosRegister $register): Warehouse
    {
        $register->loadMissing('warehouse');

        if ($register->warehouse_id === null) {
            throw new InvalidArgumentException(
                __('POS register :code has no warehouse assigned. Assign a warehouse before using ledger inventory.', [
                    'code' => $register->code,
                ])
            );
        }

        $warehouse = $register->warehouse;

        if ($warehouse === null) {
            throw new InvalidArgumentException(
                __('POS register :code warehouse is missing.', ['code' => $register->code])
            );
        }

        if (! $warehouse->is_active) {
            throw new InvalidArgumentException(
                __('POS register :code warehouse :warehouse is inactive.', [
                    'code' => $register->code,
                    'warehouse' => $warehouse->code,
                ])
            );
        }

        return $warehouse;
    }

    public function warehouseIdFromShift(PosShift $shift): ?int
    {
        $shift->loadMissing('register');

        $id = $shift->register?->warehouse_id;

        return $id !== null ? (int) $id : null;
    }
}
