@props(['min' => 1, 'max' => 99, 'value' => 1, 'name' => 'quantity', 'model' => null])

<div
    x-data="{ qty: {{ (int) $value }}, min: {{ (int) $min }}, max: {{ (int) $max }} }"
    @if ($model) x-init="$watch('qty', value => $wire.set('{{ $model }}', value))" @endif
    {{ $attributes->class(['inline-flex items-center rounded-md border border-gray-300']) }}
>
    <button
        type="button"
        @click="qty = Math.max(min, qty - 1)"
        class="flex h-9 w-9 items-center justify-center text-gray-600 transition-colors hover:bg-gray-100 disabled:opacity-40"
        :disabled="qty <= min"
        aria-label="Decrease quantity"
    >
        &minus;
    </button>

    <input
        type="number"
        name="{{ $name }}"
        x-model.number="qty"
        min="{{ $min }}"
        max="{{ $max }}"
        class="h-9 w-12 border-x border-gray-300 text-center text-sm [appearance:textfield] focus:outline-none [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
    >

    <button
        type="button"
        @click="qty = Math.min(max, qty + 1)"
        class="flex h-9 w-9 items-center justify-center text-gray-600 transition-colors hover:bg-gray-100 disabled:opacity-40"
        :disabled="qty >= max"
        aria-label="Increase quantity"
    >
        &plus;
    </button>
</div>
