@props(['block'])

@php
    use App\Models\VendorProfile;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $ids = $block->payload['ids'] ?? [];
    $vendors = empty($ids)
        ? collect()
        : VendorProfile::query()->whereIn('id', $ids)->get()->sortBy(fn ($vendor) => array_search($vendor->id, $ids))->values();
@endphp

@if ($vendors->isNotEmpty())
    {{-- py-[var(--section-spacing)]: reads the admin-configurable
         `layout_density` setting instead of a static py-10. --}}
    <section class="mx-auto max-w-7xl px-4 py-(--section-spacing) transition-all duration-700 ease-out starting:translate-y-4 starting:opacity-0 sm:px-6 lg:px-8">
        @if ($block->title)
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">{{ $block->title }}</h2>
        @endif
        @if ($block->subtitle)
            <p class="mt-1 text-sm text-gray-600">{{ $block->subtitle }}</p>
        @endif

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($vendors as $vendor)
                <x-ui.reveal :delay="($loop->index % 6) * 70">
                    <a href="#" class="flex items-center gap-4 rounded-lg border border-gray-200 p-4 transition-all duration-300 ease-out hover:-translate-y-1 hover:border-primary hover:shadow-lg">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100">
                            @if ($vendor->logo_path)
                                <img
                                    src="{{ Storage::disk('public')->url($vendor->logo_path) }}"
                                    alt="{{ $vendor->shop_name }}"
                                    class="h-full w-full object-cover"
                                >
                            @else
                                <span class="text-lg font-semibold text-secondary">{{ Str::substr($vendor->shop_name, 0, 1) }}</span>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900">{{ $vendor->shop_name }}</p>
                            @if ($vendor->description)
                                <p class="truncate text-xs text-gray-600">{{ $vendor->description }}</p>
                            @endif
                        </div>
                    </a>
                </x-ui.reveal>
            @endforeach
        </div>
    </section>
@endif
