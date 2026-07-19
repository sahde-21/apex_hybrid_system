<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCouponRequest;
use App\Http\Requests\UpdateCouponRequest;
use App\Models\Coupon;
use App\Services\CouponService;
use Illuminate\Http\RedirectResponse;

class CouponController extends Controller
{
    public function __construct(
        protected CouponService $service,
    ) {}

    public function store(StoreCouponRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('coupons.index')
            ->with('status', __('Coupons created successfully.'));
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $this->service->update($coupon, $request->validated());

        return redirect()
            ->route('coupons.index')
            ->with('status', __('Coupons updated successfully.'));
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $this->service->destroy($coupon);

        return redirect()
            ->route('coupons.index')
            ->with('status', __('Coupons deleted successfully.'));
    }
}
