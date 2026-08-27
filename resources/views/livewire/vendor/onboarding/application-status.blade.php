@php
    use App\Enums\VendorApplicationStatus;
@endphp

<div class="mx-auto flex min-h-[70vh] max-w-xl flex-col justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="rounded-xl border border-gray-200 bg-white p-8 text-center shadow-sm">
        @if ($vendorProfile->status === VendorApplicationStatus::Pending)
            <x-ui.badge variant="warning">Pending review</x-ui.badge>
            <h1 class="mt-4 text-2xl font-bold tracking-tight text-gray-900">Your vendor application is under review</h1>
            <p class="mt-2 text-sm text-gray-600">
                Thanks for applying to sell as <span class="font-medium text-gray-900">{{ $vendorProfile->shop_name }}</span>.
                An admin will review your application shortly — you'll be able to manage products and orders here as soon as it's approved.
            </p>
        @elseif ($vendorProfile->status === VendorApplicationStatus::Rejected)
            <x-ui.badge variant="danger">Not approved</x-ui.badge>
            <h1 class="mt-4 text-2xl font-bold tracking-tight text-gray-900">Your application was not approved</h1>
            @if ($vendorProfile->rejected_reason)
                <p class="mt-2 text-sm text-gray-600">{{ $vendorProfile->rejected_reason }}</p>
            @else
                <p class="mt-2 text-sm text-gray-600">Please contact support for more details.</p>
            @endif
        @elseif ($vendorProfile->status === VendorApplicationStatus::Suspended)
            <x-ui.badge variant="danger">Suspended</x-ui.badge>
            <h1 class="mt-4 text-2xl font-bold tracking-tight text-gray-900">Your vendor account has been suspended</h1>
            <p class="mt-2 text-sm text-gray-600">Please contact support for assistance.</p>
        @endif

        <div class="mt-6">
            <x-ui.button :href="url('/')" variant="secondary">Back to storefront</x-ui.button>
        </div>
    </div>
</div>
