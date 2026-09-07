<?php

namespace App\Livewire;

use App\Models\DatabaseNotification;
use App\Models\User;
use App\Services\Notifications\NotificationCenterService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class NotificationBell extends Component
{
    public function markAsRead(string $id, NotificationCenterService $center): void
    {
        $center->markAsRead($this->authenticatedUser(), $id);
        unset($this->unreadCount, $this->recent);
    }

    public function markAllAsRead(NotificationCenterService $center): void
    {
        $center->markAllAsRead($this->authenticatedUser());
        unset($this->unreadCount, $this->recent);
    }

    #[Computed]
    public function unreadCount(): int
    {
        return app(NotificationCenterService::class)->unreadCount($this->authenticatedUser());
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    #[Computed]
    public function recent(): Collection
    {
        return app(NotificationCenterService::class)
            ->queryFor($this->authenticatedUser())
            ->limit(8)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.notification-bell');
    }

    private function authenticatedUser(): User
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
