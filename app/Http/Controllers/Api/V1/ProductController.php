<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\ApiIndexRequest;
use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Http\Resources\V1\ProductResource;
use App\Http\Responses\ApiResponse;
use App\Models\Product;
use App\Services\ProductService;
use App\Support\Api\ApiAbilities;
use App\Support\Api\ApiIndexQuery;
use Illuminate\Http\JsonResponse;

class ProductController extends ApiController
{
    public function __construct(
        protected ProductService $service,
    ) {}

    public function index(ApiIndexRequest $request): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::PRODUCTS_READ);
        $this->authorize('viewAny', Product::class);

        $query = (new ApiIndexQuery(
            Product::query(),
            sortable: ['id', 'name', 'sku', 'created_at', 'updated_at', 'sale_price'],
            searchable: ['name', 'sku', 'barcode'],
            includes: ['category'],
        ))->apply($request);

        $products = $query->paginate($this->perPage($request));

        return ApiResponse::paginated(
            ProductResource::collection($products),
            __('scf.api.products.listed'),
            $this->meta($request),
        );
    }

    public function show(Product $product): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::PRODUCTS_READ);
        $this->authorize('view', $product);

        $product->load('category');

        return $this->respond(new ProductResource($product), __('scf.api.products.retrieved'));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PRODUCTS_WRITE);

        $product = $this->service->store($request->validated());
        $this->logCreated($this->actor($request), $product);

        return $this->respondCreated(new ProductResource($product->fresh('category')), __('scf.api.products.created'), $request);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PRODUCTS_WRITE);

        $product = $this->service->update($product, $request->validated());
        $this->logUpdated($this->actor($request), $product);

        return $this->respond(new ProductResource($product->fresh('category')), __('scf.api.products.updated'), $request);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PRODUCTS_WRITE);
        $this->authorize('delete', $product);

        $this->service->destroy($product);
        $this->logDeleted($this->actor(request()), $product);

        return $this->respondDeleted(__('scf.api.products.deleted'));
    }
}
