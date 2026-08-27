<div>
    @auth
        <div class="flex items-center gap-3">
            <x-ui.quantity-stepper model="quantity" :value="$quantity" />

            <x-ui.button wire:click="add" wire:loading.attr="disabled" wire:target="add">
                <span wire:loading.remove wire:target="add">Add to Cart</span>
                <span wire:loading wire:target="add">Adding&hellip;</span>
            </x-ui.button>

            @if ($added)
                <span class="text-sm font-medium text-green-600" x-data x-init="setTimeout(() => $wire.set('added', false), 2000)">
                    Added to cart
                </span>
            @endif
        </div>
    @else
        <x-ui.button href="{{ route('login') }}" variant="secondary">
            Log in to add to cart
        </x-ui.button>
    @endauth
</div>
