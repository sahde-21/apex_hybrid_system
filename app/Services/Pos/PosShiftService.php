<?php

namespace App\Services\Pos;

use App\Enums\PosShiftStatus;
use App\Models\PosRegister;
use App\Models\PosSale;
use App\Models\PosShift;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PosShiftService
{
    public function open(PosRegister $register, User $user, float $openingFloat = 0, ?string $notes = null): PosShift
    {
        if (! $register->is_active) {
            throw new InvalidArgumentException(__('Register is inactive.'));
        }

        if ($register->openShift()) {
            throw new InvalidArgumentException(__('Register already has an open shift.'));
        }

        $openForUser = PosShift::query()
            ->where('user_id', $user->id)
            ->where('status', PosShiftStatus::Open)
            ->exists();

        if ($openForUser) {
            throw new InvalidArgumentException(__('You already have an open shift.'));
        }

        return PosShift::query()->create([
            'pos_register_id' => $register->id,
            'user_id' => $user->id,
            'status' => PosShiftStatus::Open,
            'opening_float' => round($openingFloat, 2),
            'opened_at' => now(),
            'opening_notes' => $notes,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    public function close(PosShift $shift, float $closingCash, ?string $notes = null): PosShift
    {
        if (! $shift->isOpen()) {
            throw new InvalidArgumentException(__('Shift is already closed.'));
        }

        return DB::transaction(function () use ($shift, $closingCash, $notes) {
            $summary = $this->summary($shift);
            $expectedCash = round((float) $shift->opening_float + (float) $summary['cash_sales'], 2);
            $closing = round($closingCash, 2);

            $shift->update([
                'status' => PosShiftStatus::Closed,
                'closing_cash' => $closing,
                'expected_cash' => $expectedCash,
                'cash_difference' => round($closing - $expectedCash, 2),
                'closed_at' => now(),
                'closing_notes' => $notes,
                'updated_by' => auth()->id(),
            ]);

            return $shift->fresh();
        });
    }

    /**
     * @return array{
     *     sales_count: int,
     *     returns_count: int,
     *     gross_sales: float,
     *     returns_total: float,
     *     net_sales: float,
     *     tax_total: float,
     *     discount_total: float,
     *     cash_sales: float,
     *     card_sales: float,
     *     other_sales: float,
     *     payments: array<string, float>
     * }
     */
    public function summary(PosShift $shift): array
    {
        $sales = PosSale::query()
            ->with('payments')
            ->where('pos_shift_id', $shift->id)
            ->where('status', '!=', 'voided')
            ->get();

        $payments = [];
        $cash = 0.0;
        $card = 0.0;
        $other = 0.0;
        $gross = 0.0;
        $returns = 0.0;
        $tax = 0.0;
        $discount = 0.0;
        $salesCount = 0;
        $returnsCount = 0;

        foreach ($sales as $sale) {
            $total = (float) $sale->total_amount;

            if ($sale->is_return) {
                $returnsCount++;
                $returns += abs($total);
            } else {
                $salesCount++;
                $gross += $total;
                $tax += (float) $sale->tax_amount;
                $discount += (float) $sale->discount_amount;
            }

            foreach ($sale->payments as $payment) {
                $method = $payment->method->value;
                $amount = (float) $payment->amount * ($sale->is_return ? -1 : 1);
                $payments[$method] = ($payments[$method] ?? 0) + $amount;

                match ($method) {
                    'cash' => $cash += $amount,
                    'card' => $card += $amount,
                    default => $other += $amount,
                };
            }
        }

        return [
            'sales_count' => $salesCount,
            'returns_count' => $returnsCount,
            'gross_sales' => round($gross, 2),
            'returns_total' => round($returns, 2),
            'net_sales' => round($gross - $returns, 2),
            'tax_total' => round($tax, 2),
            'discount_total' => round($discount, 2),
            'cash_sales' => round($cash, 2),
            'card_sales' => round($card, 2),
            'other_sales' => round($other, 2),
            'payments' => array_map(fn ($v) => round($v, 2), $payments),
        ];
    }

    /**
     * @return array{
     *     sales_count: int,
     *     returns_count: int,
     *     gross_sales: float,
     *     returns_total: float,
     *     net_sales: float,
     *     tax_total: float,
     *     discount_total: float
     * }
     */
    public function dailySummary(DateTimeInterface|string|null $date = null, ?int $registerId = null): array
    {
        $day = $date ? \Illuminate\Support\Carbon::parse($date) : now();

        $query = PosSale::query()
            ->whereDate('created_at', $day->toDateString())
            ->where('status', '!=', 'voided')
            ->when($registerId, fn ($q) => $q->where('pos_register_id', $registerId));

        $sales = $query->get();

        $gross = 0.0;
        $returns = 0.0;
        $tax = 0.0;
        $discount = 0.0;
        $salesCount = 0;
        $returnsCount = 0;

        foreach ($sales as $sale) {
            if ($sale->is_return) {
                $returnsCount++;
                $returns += abs((float) $sale->total_amount);
            } else {
                $salesCount++;
                $gross += (float) $sale->total_amount;
                $tax += (float) $sale->tax_amount;
                $discount += (float) $sale->discount_amount;
            }
        }

        return [
            'sales_count' => $salesCount,
            'returns_count' => $returnsCount,
            'gross_sales' => round($gross, 2),
            'returns_total' => round($returns, 2),
            'net_sales' => round($gross - $returns, 2),
            'tax_total' => round($tax, 2),
            'discount_total' => round($discount, 2),
        ];
    }
}
