<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Checkout</h1>

    <div class="mt-8 grid grid-cols-1 gap-10 lg:grid-cols-3">
        <div class="space-y-8 lg:col-span-2">
            {{-- Delivery address --}}
            <section class="rounded-lg border border-gray-200 p-5">
                <h2 class="text-base font-semibold text-gray-900">Delivery Address</h2>

                @error('selectedAddressId')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror

                @if ($addresses->isNotEmpty())
                    <div class="mt-4 space-y-3">
                        @foreach ($addresses as $address)
                            <label class="flex cursor-pointer items-start gap-3 rounded-md border border-gray-200 p-3 has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                                <input
                                    type="radio"
                                    name="selectedAddressId"
                                    value="{{ $address->id }}"
                                    wire:model="selectedAddressId"
                                    wire:click="$set('addingNewAddress', false)"
                                    class="mt-1"
                                >
                                <span class="text-sm">
                                    <span class="block font-medium text-gray-900">
                                        {{ $address->recipient_name }}
                                        @if ($address->label)
                                            <span class="ml-1 text-xs font-normal text-gray-500">({{ $address->label }})</span>
                                        @endif
                                    </span>
                                    <span class="block text-gray-600">{{ $address->phone }}</span>
                                    <span class="block text-gray-600">
                                        {{ collect([$address->street_address, $address->municipality, 'Ward '.$address->ward_no, $address->district->name, $address->province->name])->filter()->implode(', ') }}
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif

                @if (! $addingNewAddress)
                    <button type="button" wire:click="showNewAddressForm" class="mt-4 text-sm font-medium text-primary hover:underline">
                        + Add a new address
                    </button>
                @else
                    <form wire:submit="saveAddress" class="mt-4 space-y-4 border-t border-gray-100 pt-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Recipient Name</label>
                                <input type="text" wire:model="newRecipientName" class="mt-1 block w-full rounded-md border border-gray-300 py-2 px-3 text-sm shadow-sm transition duration-150 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                                @error('newRecipientName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Phone</label>
                                <input type="text" wire:model="newPhone" class="mt-1 block w-full rounded-md border border-gray-300 py-2 px-3 text-sm shadow-sm transition duration-150 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                                @error('newPhone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Province</label>
                                <select wire:model.live="newProvinceId" class="mt-1 block w-full rounded-md border border-gray-300 py-2 px-3 text-sm shadow-sm transition duration-150 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                                    <option value="">Select province&hellip;</option>
                                    @foreach ($provinces as $province)
                                        <option value="{{ $province->id }}">{{ $province->name }}</option>
                                    @endforeach
                                </select>
                                @error('newProvinceId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">District</label>
                                <select wire:model="newDistrictId" class="mt-1 block w-full rounded-md border border-gray-300 py-2 px-3 text-sm shadow-sm transition duration-150 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" @disabled(! $newProvinceId)>
                                    <option value="">Select district&hellip;</option>
                                    @foreach ($districts as $district)
                                        <option value="{{ $district->id }}">{{ $district->name }}</option>
                                    @endforeach
                                </select>
                                @error('newDistrictId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Municipality</label>
                                <input type="text" wire:model="newMunicipality" class="mt-1 block w-full rounded-md border border-gray-300 py-2 px-3 text-sm shadow-sm transition duration-150 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                                @error('newMunicipality') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Ward No.</label>
                                <input type="text" wire:model="newWardNo" class="mt-1 block w-full rounded-md border border-gray-300 py-2 px-3 text-sm shadow-sm transition duration-150 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                                @error('newWardNo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Street Address</label>
                                <input type="text" wire:model="newStreetAddress" class="mt-1 block w-full rounded-md border border-gray-300 py-2 px-3 text-sm shadow-sm transition duration-150 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                                @error('newStreetAddress') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Label <span class="font-normal text-gray-400">(optional, e.g. Home, Office)</span></label>
                                <input type="text" wire:model="newLabel" class="mt-1 block w-full rounded-md border border-gray-300 py-2 px-3 text-sm shadow-sm transition duration-150 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <x-ui.button type="submit" size="sm" wire:loading.attr="disabled" wire:target="saveAddress">
                                <span wire:loading.remove wire:target="saveAddress">Save Address</span>
                                <span wire:loading wire:target="saveAddress">Saving&hellip;</span>
                            </x-ui.button>
                            @if ($addresses->isNotEmpty())
                                <x-ui.button type="button" variant="ghost" size="sm" wire:click="cancelNewAddressForm">Cancel</x-ui.button>
                            @endif
                        </div>
                    </form>
                @endif
            </section>

            {{-- Payment method --}}
            <section class="rounded-lg border border-gray-200 p-5">
                <h2 class="text-base font-semibold text-gray-900">Payment Method</h2>

                @error('paymentMethod')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror

                <div class="mt-4 space-y-3">
                    @foreach ($paymentMethods as $methodCase)
                        <label class="flex cursor-pointer items-center gap-3 rounded-md border border-gray-200 p-3 has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                            <input type="radio" name="paymentMethod" value="{{ $methodCase->value }}" wire:model="paymentMethod">
                            <span class="text-sm font-medium text-gray-900">{{ $methodCase->label() }}</span>
                        </label>
                    @endforeach
                </div>
            </section>

            {{-- Order review --}}
            <section class="rounded-lg border border-gray-200 p-5">
                <h2 class="text-base font-semibold text-gray-900">Order Review</h2>

                <div class="mt-4 space-y-4">
                    @foreach ($itemsByVendor as $vendorId => $vendorItems)
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $vendorItems->first()->vendor->shop_name }}</p>
                            <ul class="mt-2 divide-y divide-gray-100">
                                @foreach ($vendorItems as $item)
                                    <li class="flex items-center justify-between py-2 text-sm">
                                        <span class="text-gray-700">{{ $item->product->name }} &times; {{ $item->quantity }}</span>
                                        <span class="font-medium text-gray-900">
                                            {{ Illuminate\Support\Number::currency((float) $item->price_snapshot * $item->quantity, 'NPR') }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        {{-- Totals + place order --}}
        <div class="lg:col-span-1">
            <div class="sticky top-24 rounded-lg border border-gray-200 p-5">
                <h2 class="text-base font-semibold text-gray-900">Order Summary</h2>

                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Subtotal</dt>
                        <dd class="text-gray-900">{{ Illuminate\Support\Number::currency($totals['subtotal'], 'NPR') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Shipping</dt>
                        <dd class="text-gray-900">{{ Illuminate\Support\Number::currency($totals['shipping_total'], 'NPR') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Discount</dt>
                        <dd class="text-gray-900">{{ Illuminate\Support\Number::currency($totals['discount_total'], 'NPR') }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-gray-100 pt-2 text-base font-semibold">
                        <dt class="text-gray-900">Total</dt>
                        <dd class="text-gray-900">{{ Illuminate\Support\Number::currency($totals['grand_total'], 'NPR') }}</dd>
                    </div>
                </dl>

                <x-ui.button
                    type="button"
                    wire:click="placeOrder"
                    wire:loading.attr="disabled"
                    wire:target="placeOrder"
                    class="mt-6 w-full justify-center"
                    size="lg"
                >
                    <span wire:loading.remove wire:target="placeOrder">Place Order</span>
                    <span wire:loading wire:target="placeOrder">Placing Order&hellip;</span>
                </x-ui.button>
            </div>
        </div>
    </div>
</div>
