@props(['title' => 'Nothing here yet', 'message' => null])

<div {{ $attributes->class(['flex flex-col items-center justify-center rounded-lg border border-dashed border-gray-300 px-6 py-16 text-center']) }}>
    <svg viewBox="0 0 24 24" class="h-12 w-12 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5l1.5-3h15l1.5 3M3 7.5v10.5A1.5 1.5 0 004.5 19.5h15a1.5 1.5 0 001.5-1.5V7.5M3 7.5h18M9 12h6" />
    </svg>

    <p class="mt-4 text-base font-semibold text-gray-900">{{ $title }}</p>

    @if ($message)
        <p class="mt-1 text-sm text-gray-500">{{ $message }}</p>
    @endif

    @isset($slot)
        @if (trim($slot) !== '')
            <div class="mt-4">{{ $slot }}</div>
        @endif
    @endisset
</div>
