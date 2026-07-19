<?php

namespace App\Livewire;

use App\Services\Notifications\NotificationCenterService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class NotificationBell extends Component
{
    public function markAsRead(string $id, NotificationCenterService $center): void
    {
        $center->markAsRead(auth()->user(), $id);
        unset($this->unreadCount, $this->recent);
    }

    public function markAllAsRead(NotificationCenterService $center): void
    {
        $center->markAllAsRead(auth()->user());
        unset($this->unreadCount, $this->recent);
    }

    #[Computed]
    public function unreadCount(): int
    {
        return app(NotificationCenterService::class)->unreadCount(auth()->user());
    }

    #[Computed]
    public function recent()
    {
        return app(NotificationCenterService::class)
            ->queryFor(auth()->user())
            ->limit(8)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.notification-bell');
    }
}
