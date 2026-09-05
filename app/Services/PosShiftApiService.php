<?php

namespace App\Services;

use App\Enums\PosShiftStatus;
use App\Exceptions\Api\BusinessConflictException;
use App\Models\PosRegister;
use App\Models\PosShift;
use App\Models\User;
use App\Services\Pos\PosShiftService as DomainPosShiftService;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class PosShiftApiService
{
    public function __construct(
        protected DomainPosShiftService $shifts,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data, User $user): PosShift
    {
        $register = PosRegister::query()->findOrFail((int) $data['pos_register_id']);

        try {
            return $this->shifts->open(
                $register,
                $user,
                (float) $data['opening_amount'],
                $data['opening_notes'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            throw new BusinessConflictException($e->getMessage(), [
                'pos_register_id' => [$e->getMessage()],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PosShift $shift, array $data, User $user): PosShift
    {
        if (! $shift->isOpen()) {
            throw new BusinessConflictException(__('Shift is already closed.'), [
                'status' => [__('Shift is already closed.')],
            ]);
        }

        $closingAmount = $data['closing_amount'] ?? null;
        $status = $data['status'] ?? null;
        $wantsClose = $closingAmount !== null || $status === PosShiftStatus::Closed->value;

        if ($wantsClose) {
            if ($closingAmount === null) {
                throw ValidationException::withMessages([
                    'closing_amount' => [__('Closing amount is required to close a shift.')],
                ]);
            }

            try {
                return $this->shifts->close($shift, (float) $closingAmount, $data['closing_notes'] ?? null);
            } catch (InvalidArgumentException $e) {
                throw new BusinessConflictException($e->getMessage(), [
                    'status' => [$e->getMessage()],
                ]);
            }
        }

        $shift->update([
            'opening_notes' => array_key_exists('opening_notes', $data)
                ? $data['opening_notes']
                : $shift->opening_notes,
            'updated_by' => $user->id,
        ]);

        return $shift->fresh();
    }

    public function destroy(PosShift $shift): void
    {
        if (! $shift->isOpen()) {
            throw new BusinessConflictException(__('Shift is already closed.'), [
                'status' => [__('Shift is already closed.')],
            ]);
        }

        $shift->delete();
    }
}
