<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGiftCardRequest;
use App\Http\Requests\UpdateGiftCardRequest;
use App\Models\GiftCard;
use App\Services\GiftCardService;
use Illuminate\Http\RedirectResponse;

class GiftCardController extends Controller
{
    public function __construct(
        protected GiftCardService $service,
    ) {}

    public function store(StoreGiftCardRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('gift-cards.index')
            ->with('status', __('Gift cards created successfully.'));
    }

    public function update(UpdateGiftCardRequest $request, GiftCard $giftCard): RedirectResponse
    {
        $this->service->update($giftCard, $request->validated());

        return redirect()
            ->route('gift-cards.index')
            ->with('status', __('Gift cards updated successfully.'));
    }

    public function destroy(GiftCard $giftCard): RedirectResponse
    {
        $this->service->destroy($giftCard);

        return redirect()
            ->route('gift-cards.index')
            ->with('status', __('Gift cards deleted successfully.'));
    }
}
