<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Models\Campaign;
use App\Services\CampaignService;
use Illuminate\Http\RedirectResponse;

class CampaignController extends Controller
{
    public function __construct(
        protected CampaignService $service,
    ) {}

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('campaigns.index')
            ->with('status', __('Campaigns created successfully.'));
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->service->update($campaign, $request->validated());

        return redirect()
            ->route('campaigns.index')
            ->with('status', __('Campaigns updated successfully.'));
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $this->service->destroy($campaign);

        return redirect()
            ->route('campaigns.index')
            ->with('status', __('Campaigns deleted successfully.'));
    }
}
