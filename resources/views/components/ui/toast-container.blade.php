@props([])

{{--
    Global toast/notification stack. Mounted once per layout. Listens for the
    Livewire browser event dispatched from PHP as:

        $this->dispatch('notify', message: 'Added to cart', type: 'success');

    Livewire dispatches this as `window.dispatchEvent(new CustomEvent('notify', { detail: { message, type } }))`,
    so a plain `x-on:notify.window` listener picks it up — no Livewire JS
    hooks needed. `type` is one of 'success' | 'error' | 'info' (defaults to
    'info'), mirroring the color variants used by <x-ui.badge>.
--}}
<div
    id="toast-container"
    x-data="{
        toasts: [],
        add(detail) {
            const toast = {
                id: (crypto.randomUUID ? crypto.randomUUID() : Date.now() + '-' + Math.random()),
                message: detail?.message ?? '',
                type: detail?.type ?? 'info',
            };
            this.toasts.push(toast);
            setTimeout(() => this.remove(toast.id), 4000);
        },
        remove(id) {
            this.toasts = this.toasts.filter((toast) => toast.id !== id);
        },
    }"
    x-on:notify.window="add($event.detail)"
    aria-live="polite"
    aria-atomic="true"
    class="pointer-events-none fixed inset-x-0 top-4 z-50 flex flex-col items-center gap-2 px-4 sm:items-end sm:px-6"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            x-on:click="remove(toast.id)"
            role="alert"
            class="pointer-events-auto flex w-full max-w-sm cursor-pointer items-start gap-3 rounded-md border px-4 py-3 text-sm shadow-lg sm:w-auto"
            :class="{
                'border-green-200 bg-green-50 text-green-800': toast.type === 'success',
                'border-red-200 bg-red-50 text-red-800': toast.type === 'error',
                'border-primary/20 bg-primary/5 text-gray-800': toast.type === 'info',
            }"
        >
            <span class="flex-1" x-text="toast.message"></span>
            <button
                type="button"
                class="shrink-0 text-current opacity-60 transition-opacity hover:opacity-100"
                aria-label="Dismiss notification"
                x-on:click.stop="remove(toast.id)"
            >
                &times;
            </button>
        </div>
    </template>
</div>
