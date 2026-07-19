<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNotificationTemplateRequest;
use App\Http\Requests\UpdateNotificationTemplateRequest;
use App\Models\NotificationTemplate;
use App\Services\NotificationTemplateService;
use Illuminate\Http\RedirectResponse;

class NotificationTemplateController extends Controller
{
    public function __construct(
        protected NotificationTemplateService $service,
    ) {}

    public function store(StoreNotificationTemplateRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('notification-templates.index')
            ->with('status', __('Notification templates created successfully.'));
    }

    public function update(UpdateNotificationTemplateRequest $request, NotificationTemplate $notificationTemplate): RedirectResponse
    {
        $this->service->update($notificationTemplate, $request->validated());

        return redirect()
            ->route('notification-templates.index')
            ->with('status', __('Notification templates updated successfully.'));
    }

    public function destroy(NotificationTemplate $notificationTemplate): RedirectResponse
    {
        $this->service->destroy($notificationTemplate);

        return redirect()
            ->route('notification-templates.index')
            ->with('status', __('Notification templates deleted successfully.'));
    }
}
