<?php

namespace App\Services\Pos;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\Variant;
use App\Models\PosFavorite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PosCatalogService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function search(?string $query = null, ?int $categoryId = null, ?int $userId = null, int $limit = 48): Collection
    {
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

        return $products->map(fn (Product $product) => $this->mapProduct($product, in_array($product->id, $favoriteIds, true)));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByScan(string $code): ?array
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
            return $this->mapVariant($variant);
        }

        $product = Product::query()
            ->with(['category', 'variants' => fn ($q) => $q->where('is_active', true)])
            ->where('is_active', true)
            ->where(function (Builder $q) use ($code): void {
                $q->where('barcode', $code)->orWhere('sku', $code);
            })
            ->first();

        return $product ? $this->mapProduct($product, (bool) $product->is_favorite) : null;
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
     * @return array<string, mixed>
     */
    public function mapProduct(Product $product, bool $isFavorite = false): array
    {
        return [
            'type' => 'product',
            'product_id' => $product->id,
            'variant_id' => null,
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'sale_price' => (float) $product->sale_price,
            'stock_quantity' => (int) $product->stock_quantity,
            'category_id' => $product->category_id,
            'category_name' => $product->category?->name,
            'is_favorite' => $isFavorite || (bool) $product->is_favorite,
            'qr_payload' => $product->barcode ?: $product->sku,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function mapVariant(Variant $variant): array
    {
        $product = $variant->product;

        return [
            'type' => 'variant',
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'name' => trim(($product?->name ?? '').' — '.$variant->name),
            'sku' => $variant->sku,
            'barcode' => $variant->barcode,
            'sale_price' => (float) $variant->sale_price,
            'stock_quantity' => (int) $variant->stock_quantity,
            'category_id' => $product?->category_id,
            'category_name' => $product?->category?->name,
            'is_favorite' => false,
            'qr_payload' => $variant->barcode ?: $variant->sku,
        ];
    }
}
