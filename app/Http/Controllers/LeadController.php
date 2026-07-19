<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Models\Lead;
use App\Services\LeadService;
use Illuminate\Http\RedirectResponse;

class LeadController extends Controller
{
    public function __construct(
        protected LeadService $service,
    ) {}

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('leads.index')
            ->with('status', __('Lead management created successfully.'));
    }

    public function update(UpdateLeadRequest $request, Lead $lead): RedirectResponse
    {
        $this->service->update($lead, $request->validated());

        return redirect()
            ->route('leads.index')
            ->with('status', __('Lead management updated successfully.'));
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $this->service->destroy($lead);

        return redirect()
            ->route('leads.index')
            ->with('status', __('Lead management deleted successfully.'));
    }
}
