<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCrmInteractionRequest;
use App\Http\Requests\UpdateCrmInteractionRequest;
use App\Models\CrmInteraction;
use App\Services\CrmInteractionService;
use Illuminate\Http\RedirectResponse;

class CrmInteractionController extends Controller
{
    public function __construct(
        protected CrmInteractionService $service,
    ) {}

    public function store(StoreCrmInteractionRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('crm-interactions.index')
            ->with('status', __('CRM interaction created successfully.'));
    }

    public function update(UpdateCrmInteractionRequest $request, CrmInteraction $crmInteraction): RedirectResponse
    {
        $this->service->update($crmInteraction, $request->validated());

        return redirect()
            ->route('crm-interactions.index')
            ->with('status', __('CRM interaction updated successfully.'));
    }

    public function destroy(CrmInteraction $crmInteraction): RedirectResponse
    {
        $this->service->destroy($crmInteraction);

        return redirect()
            ->route('crm-interactions.index')
            ->with('status', __('CRM interaction deleted successfully.'));
    }
}
