<?php

namespace App\Support\Sales;

final class DocumentLineCalculator
{
    /**
     * @param  list<array<string, mixed>>  $lines
     * @return array{subtotal: float, discount: float, tax: float, total: float, lines: list<array<string, mixed>>}
     */
    public static function summarize(array $lines): array
    {
        $normalized = [];
        $subtotal = 0.0;
        $discount = 0.0;
        $tax = 0.0;

        foreach (array_values($lines) as $index => $line) {
            $qty = max(0, (float) ($line['quantity'] ?? 0));
            $price = max(0, (float) ($line['unit_price'] ?? 0));
            $lineDiscount = max(0, (float) ($line['discount_amount'] ?? 0));
            $lineTax = max(0, (float) ($line['tax_amount'] ?? 0));
            $base = round(($qty * $price) - $lineDiscount, 2);
            $lineTotal = round($base + $lineTax, 2);

            $normalized[] = [
                'product_id' => $line['product_id'] ?? null,
                'quotation_line_id' => $line['quotation_line_id'] ?? null,
                'sale_order_line_id' => $line['sale_order_line_id'] ?? null,
                'line_number' => $index + 1,
                'description' => (string) ($line['description'] ?? ''),
                'quantity' => $qty,
                'unit_price' => $price,
                'discount_amount' => $lineDiscount,
                'tax_amount' => $lineTax,
                'line_total' => $lineTotal,
            ];

            $subtotal += round($qty * $price, 2);
            $discount += $lineDiscount;
            $tax += $lineTax;
        }

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'tax' => round($tax, 2),
            'total' => round($subtotal - $discount + $tax, 2),
            'lines' => $normalized,
        ];
    }
}
