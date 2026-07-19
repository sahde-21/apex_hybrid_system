<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $service,
    ) {}

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('products.index')
            ->with('status', __('Product created successfully.'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->service->update($product, $request->validated());

        return redirect()
            ->route('products.index')
            ->with('status', __('Product updated successfully.'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->service->destroy($product);

        return redirect()
            ->route('products.index')
            ->with('status', __('Product deleted successfully.'));
    }
}
