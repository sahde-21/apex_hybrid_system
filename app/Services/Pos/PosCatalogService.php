<?php

namespace App\Services\Pos;

use App\Models\PosFavorite;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockLevel;
use App\Models\TaxRate;
use App\Models\Variant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PosCatalogService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function search(
        ?string $query = null,
        ?int $categoryId = null,
        ?int $userId = null,
        int $limit = 48,
        ?int $warehouseId = null,
    ): Collection {
        $products = Product::query()
            ->with(['category', 'variants' => fn ($q) => $q->where('is_active', true)])
            ->where('is_active', true)
            ->when($categoryId, fn (Builder $q) => $q->where('category_id', $categoryId))
            ->when($query, function (Builder $q) use ($query): void {
                $term = '%'.trim($query).'%';
                $q->where(function (Builder $inner) use ($term): void {
                    $inner->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term)
                        ->orWhere('barcode', 'like', $term)
                        ->orWhereHas('variants', function (Builder $variants) use ($term): void {
                            $variants->where('sku', 'like', $term)
                                ->orWhere('barcode', 'like', $term)
                                ->orWhere('name', 'like', $term);
                        });
                });
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();

        $favoriteIds = $userId
            ? PosFavorite::query()->where('user_id', $userId)->pluck('product_id')->all()
            : [];

        $availability = $this->resolveAvailabilityMap($products, $warehouseId);

        return $products->map(fn (Product $product) => $this->mapProduct(
            $product,
            in_array($product->id, $favoriteIds, true),
            $availability,
            $warehouseId,
        ));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByScan(string $code, ?int $warehouseId = null): ?array
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        $variant = Variant::query()
            ->with('product.category')
            ->where('is_active', true)
            ->where(function (Builder $q) use ($code): void {
                $q->where('barcode', $code)->orWhere('sku', $code);
            })
            ->first();

        if ($variant) {
            $availability = $this->resolveAvailabilityMap(
                collect([$variant->product])->filter(),
                $warehouseId,
            );

            return $this->mapVariant($variant, $availability, $warehouseId);
        }

        $product = Product::query()
            ->with(['category', 'variants' => fn ($q) => $q->where('is_active', true)])
            ->where('is_active', true)
            ->where(function (Builder $q) use ($code): void {
                $q->where('barcode', $code)->orWhere('sku', $code);
            })
            ->first();

        if (! $product) {
            return null;
        }

        $availability = $this->resolveAvailabilityMap(collect([$product]), $warehouseId);

        return $this->mapProduct($product, (bool) $product->is_favorite, $availability, $warehouseId);
    }

    /**
     * @return Collection<int, ProductCategory>
     */
    public function categories(): Collection
    {
        return ProductCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function defaultTaxRate(): float
    {
        $rate = TaxRate::query()->where('is_active', true)->orderBy('id')->value('rate');

        return (float) ($rate ?? 0);
    }

    /**
     * @param  array<string, int>|null  $availability
     * @return array<string, mixed>
     */
    public function mapProduct(
        Product $product,
        bool $isFavorite = false,
        ?array $availability = null,
        ?int $warehouseId = null,
    ): array {
        return [
            'type' => 'product',
            'product_id' => $product->id,
            'variant_id' => null,
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'sale_price' => (float) $product->sale_price,
            'stock_quantity' => $this->stockQuantityFor(
                productId: $product->id,
                variantId: null,
                legacyQuantity: (int) $product->stock_quantity,
                availability: $availability,
                warehouseId: $warehouseId,
            ),
            'category_id' => $product->category_id,
            'category_name' => $product->category?->name,
            'is_favorite' => $isFavorite || (bool) $product->is_favorite,
            'qr_payload' => $product->barcode ?: $product->sku,
        ];
    }

    /**
     * @param  array<string, int>|null  $availability
     * @return array<string, mixed>
     */
    public function mapVariant(
        Variant $variant,
        ?array $availability = null,
        ?int $warehouseId = null,
    ): array {
        $product = $variant->product;

        return [
            'type' => 'variant',
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'name' => trim(($product->name ?? '').' — '.$variant->name),
            'sku' => $variant->sku,
            'barcode' => $variant->barcode,
            'sale_price' => (float) $variant->sale_price,
            'stock_quantity' => $this->stockQuantityFor(
                productId: $variant->product_id,
                variantId: $variant->id,
                legacyQuantity: (int) $variant->stock_quantity,
                availability: $availability,
                warehouseId: $warehouseId,
            ),
            'category_id' => $product?->category_id,
            'category_name' => $product?->category?->name,
            'is_favorite' => false,
            'qr_payload' => $variant->barcode ?: $variant->sku,
        ];
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return array<string, int>
     */
    protected function resolveAvailabilityMap(Collection $products, ?int $warehouseId): array
    {
        if (! $this->usesLedgerAvailability($warehouseId) || $products->isEmpty()) {
            return [];
        }

        $productIds = $products->pluck('id')->all();
        $variantIds = $products
            ->flatMap(fn (Product $product) => $product->relationLoaded('variants')
                ? $product->variants->pluck('id')
                : [])
            ->unique()
            ->values()
            ->all();

        $levels = StockLevel::query()
            ->where('warehouse_id', $warehouseId)
            ->whereIn('product_id', $productIds)
            ->get(['product_id', 'variant_id', 'on_hand', 'reserved']);

        $map = [];

        foreach ($levels as $level) {
            if ($level->variant_id !== null && $variantIds !== [] && ! in_array($level->variant_id, $variantIds, true)) {
                // Still include — mapVariant may request a specific variant not in product.variants load.
            }

            $map[$this->availabilityKey((int) $level->product_id, $level->variant_id !== null ? (int) $level->variant_id : null)]
                = max(0, (int) $level->on_hand - (int) $level->reserved);
        }

        return $map;
    }

    /**
     * @param  array<string, int>|null  $availability
     */
    protected function stockQuantityFor(
        int $productId,
        ?int $variantId,
        int $legacyQuantity,
        ?array $availability,
        ?int $warehouseId,
    ): int {
        if (! $this->usesLedgerAvailability($warehouseId)) {
            return $legacyQuantity;
        }

        $key = $this->availabilityKey($productId, $variantId);

        if ($availability !== null && array_key_exists($key, $availability)) {
            return $availability[$key];
        }

        if ($availability !== null) {
            return 0;
        }

        $level = StockLevel::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->when(
                $variantId === null,
                fn (Builder $q) => $q->whereNull('variant_id'),
                fn (Builder $q) => $q->where('variant_id', $variantId),
            )
            ->first();

        if ($level === null) {
            return 0;
        }

        return max(0, (int) $level->on_hand - (int) $level->reserved);
    }

    protected function usesLedgerAvailability(?int $warehouseId): bool
    {
        return (bool) config('inventory.ledger_enabled', false) && $warehouseId !== null;
    }

    protected function availabilityKey(int $productId, ?int $variantId): string
    {
        return $productId.':'.($variantId ?? '0');
    }
}
