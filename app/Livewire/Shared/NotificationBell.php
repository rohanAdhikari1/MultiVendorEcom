<?php

namespace App\Livewire\Shared;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationBell extends Component
{
    public int $unreadCount = 0;

    public function mount(): void
    {
        $this->refreshCount();
    }

    #[On('notifications-refreshed')]
    public function refreshCount(): void
    {
        $this->unreadCount = (int) Auth::user()?->unreadNotifications()->count();
    }

    /**
     * Marks a single notification as read. Scoped to the authenticated
     * user's own notifications() relation -- filtered by notifiable_id/
     * notifiable_type under the hood -- so passing another user's
     * notification id here simply matches nothing rather than mutating it.
     */
    public function markAsRead(string $notificationId): void
    {
        /** @var DatabaseNotification|null $notification */
        $notification = Auth::user()?->notifications()->whereKey($notificationId)->first();

        $notification?->markAsRead();

        $this->refreshCount();
    }

    public function markAllAsRead(): void
    {
        Auth::user()?->unreadNotifications->markAsRead();

        $this->refreshCount();
    }

    public function render(): View
    {
        /** @var Collection<int, DatabaseNotification> $notifications */
        $notifications = Auth::user()?->notifications()->latest()->limit(10)->get() ?? new Collection;

        return view('livewire.shared.notification-bell', [
            'notifications' => $notifications,
        ]);
    }
}
