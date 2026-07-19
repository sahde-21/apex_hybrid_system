<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoyaltyProgramRequest;
use App\Http\Requests\UpdateLoyaltyProgramRequest;
use App\Models\LoyaltyProgram;
use App\Services\LoyaltyProgramService;
use Illuminate\Http\RedirectResponse;

class LoyaltyProgramController extends Controller
{
    public function __construct(
        protected LoyaltyProgramService $service,
    ) {}

    public function store(StoreLoyaltyProgramRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('loyalty-programs.index')
            ->with('status', __('Loyalty programs created successfully.'));
    }

    public function update(UpdateLoyaltyProgramRequest $request, LoyaltyProgram $loyaltyProgram): RedirectResponse
    {
        $this->service->update($loyaltyProgram, $request->validated());

        return redirect()
            ->route('loyalty-programs.index')
            ->with('status', __('Loyalty programs updated successfully.'));
    }

    public function destroy(LoyaltyProgram $loyaltyProgram): RedirectResponse
    {
        $this->service->destroy($loyaltyProgram);

        return redirect()
            ->route('loyalty-programs.index')
            ->with('status', __('Loyalty programs deleted successfully.'));
    }
}
