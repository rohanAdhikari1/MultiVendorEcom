{{--
    Small anchored dropdown, following the same Alpine open-state +
    x-transition structure as the mini-cart drawer in cart-indicator.blade.php
    (click-to-open, Escape closes, matching transition timings) but rendered
    as a compact panel rather than a full slide-in drawer, since a
    notification list doesn't need the same screen real estate as the cart.
--}}
<div x-data="{ open: false }" x-on:keydown.escape.window="open = false" class="relative">
    <button
        type="button"
        id="notification-bell-trigger"
        x-on:click="open = ! open"
        class="relative inline-flex items-center rounded-md p-2 text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-900"
        aria-label="Notifications{{ $unreadCount > 0 ? ": {$unreadCount} unread" : '' }}"
        aria-haspopup="dialog"
        :aria-expanded="open.toString()"
        aria-controls="notification-bell-panel"
    >
        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>

        @if ($unreadCount > 0)
            <span class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-semibold text-white">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div
        id="notification-bell-panel"
        x-cloak
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        x-on:click.outside="open = false"
        class="absolute right-0 z-50 mt-2 max-h-96 w-80 overflow-y-auto rounded-md border border-gray-200 bg-white shadow-lg"
        role="dialog"
        aria-label="Notifications"
    >
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
            <h2 class="text-sm font-semibold text-gray-900">Notifications</h2>
            @if ($unreadCount > 0)
                <button
                    type="button"
                    id="notification-mark-all-read"
                    wire:click="markAllAsRead"
                    class="text-xs font-medium text-primary hover:underline"
                >
                    Mark all as read
                </button>
            @endif
        </div>

        <div class="divide-y divide-gray-100">
            @forelse ($notifications as $notification)
                <a
                    href="{{ $notification->data['url'] ?? '#' }}"
                    wire:key="notification-{{ $notification->id }}"
                    wire:click="markAsRead('{{ $notification->id }}')"
                    @class([
                        'flex flex-col gap-1 px-4 py-3 text-left transition-colors hover:bg-gray-50',
                        'bg-primary/5' => $notification->read_at === null,
                    ])
                >
                    <span class="flex items-center gap-2 text-sm font-medium text-gray-900">
                        @if ($notification->read_at === null)
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-primary" aria-hidden="true"></span>
                        @endif
                        <span class="truncate">{{ $notification->data['message'] ?? 'Notification' }}</span>
                    </span>
                    <span class="text-xs text-gray-500">{{ $notification->created_at?->diffForHumans() }}</span>
                </a>
            @empty
                <p class="px-4 py-6 text-center text-sm text-gray-500">No notifications yet.</p>
            @endforelse
        </div>
    </div>
</div>
