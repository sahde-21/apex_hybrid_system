<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContractRequest;
use App\Http\Requests\UpdateContractRequest;
use App\Models\Contract;
use App\Services\ContractService;
use Illuminate\Http\RedirectResponse;

class ContractController extends Controller
{
    public function __construct(
        protected ContractService $service,
    ) {}

    public function store(StoreContractRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('contracts.index')
            ->with('status', __('Contracts created successfully.'));
    }

    public function update(UpdateContractRequest $request, Contract $contract): RedirectResponse
    {
        $this->service->update($contract, $request->validated());

        return redirect()
            ->route('contracts.index')
            ->with('status', __('Contracts updated successfully.'));
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        $this->service->destroy($contract);

        return redirect()
            ->route('contracts.index')
            ->with('status', __('Contracts deleted successfully.'));
    }
}
