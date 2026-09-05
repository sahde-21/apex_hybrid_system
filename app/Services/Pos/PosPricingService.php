<?php

namespace App\Services\Pos;

use App\Models\Coupon;
use InvalidArgumentException;

class PosPricingService
{
    /**
     * @param  list<array{unit_price: float|int|string, quantity: int, discount_amount?: float|int|string, tax_rate?: float|int|string}>  $items
     * @return array{subtotal: float, item_discount: float, coupon_discount: float, discount_total: float, tax: float, total: float, lines: list<array{unit_price: float, quantity: int, discount_amount: float, tax_rate: float, tax_amount: float, line_total: float}>}
     */
    public function calculate(
        array $items,
        float $cartDiscount = 0,
        ?Coupon $coupon = null,
        float $defaultTaxRate = 0,
    ): array {
        $lines = [];
        $subtotal = 0.0;
        $itemDiscount = 0.0;
        $tax = 0.0;

        foreach ($items as $item) {
            $quantity = max(1, (int) $item['quantity']);
            $unitPrice = round((float) $item['unit_price'], 2);
            $lineDiscount = round((float) ($item['discount_amount'] ?? 0), 2);
            $taxRate = round((float) ($item['tax_rate'] ?? $defaultTaxRate), 2);
            $net = max(0, ($unitPrice * $quantity) - $lineDiscount);
            $lineTax = round($net * ($taxRate / 100), 2);
            $lineTotal = round($net + $lineTax, 2);

            $subtotal += $unitPrice * $quantity;
            $itemDiscount += $lineDiscount;
            $tax += $lineTax;

            $lines[] = [
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'discount_amount' => $lineDiscount,
                'tax_rate' => $taxRate,
                'tax_amount' => $lineTax,
                'line_total' => $lineTotal,
            ];
        }

        $netBeforeCoupon = max(0, $subtotal - $itemDiscount);
        $couponDiscount = $coupon ? $this->couponDiscount($coupon, $netBeforeCoupon) : 0.0;
        $manualDiscount = max(0, round($cartDiscount, 2));
        $discountTotal = round(min($netBeforeCoupon, $itemDiscount + $couponDiscount + $manualDiscount), 2);

        // Recalculate tax proportionally after cart-level discounts.
        $taxableBase = max(0, $subtotal - $discountTotal);
        $effectiveTax = $subtotal > 0
            ? round($tax * ($taxableBase / max($subtotal - $itemDiscount, 0.0001)), 2)
            : 0.0;

        $total = round($taxableBase + $effectiveTax, 2);

        return [
            'subtotal' => round($subtotal, 2),
            'item_discount' => round($itemDiscount, 2),
            'coupon_discount' => $couponDiscount,
            'discount_total' => $discountTotal,
            'tax' => $effectiveTax,
            'total' => $total,
            'lines' => $lines,
        ];
    }

    public function couponDiscount(Coupon $coupon, float $amount): float
    {
        if (! $coupon->isRedeemable()) {
            throw new InvalidArgumentException(__('Coupon is not redeemable.'));
        }

        $type = strtolower((string) $coupon->discount_type);
        $value = (float) $coupon->discount_value;

        $discount = match ($type) {
            'percentage', 'percent' => $amount * ($value / 100),
            default => $value,
        };

        return round(min($amount, max(0, $discount)), 2);
    }
}
