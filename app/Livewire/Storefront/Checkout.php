<?php

namespace App\Livewire\Storefront;

use App\Actions\Checkout\CalculateOrderTotalsAction;
use App\Actions\Checkout\PlaceOrderAction;
use App\Enums\PaymentMethod;
use App\Enums\PaymentTransactionStatus;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\District;
use App\Models\PaymentTransaction;
use App\Models\Province;
use App\Models\User;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Checkout extends Component
{
    public ?int $selectedAddressId = null;

    public bool $addingNewAddress = false;

    public string $newLabel = '';

    public string $newRecipientName = '';

    public string $newPhone = '';

    public ?int $newProvinceId = null;

    public ?int $newDistrictId = null;

    public string $newMunicipality = '';

    public string $newWardNo = '';

    public string $newStreetAddress = '';

    public string $paymentMethod = 'cod';

    public function mount(): void
    {
        if ($this->currentCartItems()->isEmpty()) {
            $this->redirect(route('cart'), navigate: false);

            return;
        }

        $defaultAddress = $this->addresses()->firstWhere('is_default', true) ?? $this->addresses()->first();

        if ($defaultAddress !== null) {
            $this->selectedAddressId = $defaultAddress->id;
        } else {
            $this->addingNewAddress = true;
        }
    }

    public function updatedNewProvinceId(): void
    {
        $this->newDistrictId = null;
    }

    public function showNewAddressForm(): void
    {
        $this->addingNewAddress = true;
        $this->selectedAddressId = null;
    }

    public function cancelNewAddressForm(): void
    {
        $this->addingNewAddress = false;
        $this->reset(['newLabel', 'newRecipientName', 'newPhone', 'newProvinceId', 'newDistrictId', 'newMunicipality', 'newWardNo', 'newStreetAddress']);

        $fallback = $this->addresses()->firstWhere('is_default', true) ?? $this->addresses()->first();
        $this->selectedAddressId = $fallback?->id;
    }

    public function saveAddress(): void
    {
        $validated = $this->validate([
            'newLabel' => ['nullable', 'string', 'max:50'],
            'newRecipientName' => ['required', 'string', 'max:255'],
            'newPhone' => ['required', 'string', 'max:20'],
            'newProvinceId' => ['required', 'integer', 'exists:provinces,id'],
            'newDistrictId' => [
                'required',
                'integer',
                Rule::exists('districts', 'id')->where('province_id', $this->newProvinceId),
            ],
            'newMunicipality' => ['required', 'string', 'max:255'],
            'newWardNo' => ['nullable', 'string', 'max:10'],
            'newStreetAddress' => ['nullable', 'string', 'max:500'],
        ], [], [
            'newRecipientName' => 'recipient name',
            'newPhone' => 'phone number',
            'newProvinceId' => 'province',
            'newDistrictId' => 'district',
            'newMunicipality' => 'municipality',
            'newWardNo' => 'ward number',
            'newStreetAddress' => 'street address',
        ]);

        $address = Address::query()->create([
            'user_id' => Auth::id(),
            'label' => $validated['newLabel'] ?: null,
            'recipient_name' => $validated['newRecipientName'],
            'phone' => $validated['newPhone'],
            'province_id' => $validated['newProvinceId'],
            'district_id' => $validated['newDistrictId'],
            'municipality' => $validated['newMunicipality'],
            'ward_no' => $validated['newWardNo'] ?: null,
            'street_address' => $validated['newStreetAddress'] ?: null,
            'is_default' => $this->addresses()->isEmpty(),
        ]);

        $this->selectedAddressId = $address->id;
        $this->addingNewAddress = false;
        $this->reset(['newLabel', 'newRecipientName', 'newPhone', 'newProvinceId', 'newDistrictId', 'newMunicipality', 'newWardNo', 'newStreetAddress']);
    }

    public function placeOrder(PlaceOrderAction $placeOrderAction, PaymentGatewayManager $gatewayManager): void
    {
        $this->validate([
            'selectedAddressId' => ['required', 'integer'],
            'paymentMethod' => ['required', Rule::in(array_map(fn (PaymentMethod $case) => $case->value, PaymentMethod::cases()))],
        ]);

        $address = Address::query()
            ->where('user_id', Auth::id())
            ->find($this->selectedAddressId);

        if ($address === null) {
            $this->addError('selectedAddressId', 'Please choose a valid delivery address.');

            return;
        }

        $method = PaymentMethod::from($this->paymentMethod);

        /** @var User $user */
        $user = Auth::user();

        $order = $placeOrderAction->execute($user, $address, $method);

        $this->dispatch('cart-updated');

        if ($method === PaymentMethod::Cod) {
            $this->redirect(route('orders.confirmation', $order->order_number), navigate: false);

            return;
        }

        $transaction = PaymentTransaction::query()->create([
            'order_id' => $order->id,
            'gateway' => $method,
            'amount' => $order->grand_total,
            'status' => PaymentTransactionStatus::Initiated,
        ]);

        $driver = $gatewayManager->driver($method);
        $result = $driver->initiate($order);

        if ($result->reference !== null) {
            $transaction->gateway_reference = $result->reference;
            $transaction->save();
        }

        if ($result->type === 'redirect' && $result->url !== null) {
            $this->redirect($result->url, navigate: false);

            return;
        }

        if ($result->type === 'form' && $result->formAction !== null && $result->formFields !== null) {
            $url = route('payments.form-redirect', ['gateway' => $method->value]).'?'.http_build_query($result->formFields);

            $this->redirect($url, navigate: false);

            return;
        }

        // 'immediate' (or any unexpected shape) for an online gateway
        // shouldn't happen, but fall back to the confirmation page rather
        // than stranding the customer.
        $this->redirect(route('orders.confirmation', $order->order_number), navigate: false);
    }

    /**
     * @return Collection<int, Address>
     */
    protected function addresses(): Collection
    {
        /** @var Collection<int, Address> $addresses */
        $addresses = Address::query()->where('user_id', Auth::id())->with(['province', 'district'])->orderByDesc('is_default')->get();

        return $addresses;
    }

    /**
     * @return Collection<int, CartItem>
     */
    protected function currentCartItems(): Collection
    {
        $cart = Cart::query()->where('user_id', Auth::id())->with('items.product', 'items.vendor', 'items.productVariant')->first();

        /** @var Collection<int, CartItem> $items */
        $items = $cart->items ?? new Collection;

        return $items;
    }

    public function render(): View
    {
        $items = $this->currentCartItems();
        $totals = app(CalculateOrderTotalsAction::class)->execute($items);

        return view('livewire.storefront.checkout', [
            'addresses' => $this->addresses(),
            'provinces' => Province::query()->orderBy('name')->get(),
            'districts' => $this->newProvinceId
                ? District::query()->where('province_id', $this->newProvinceId)->orderBy('name')->get()
                : new Collection,
            'paymentMethods' => PaymentMethod::cases(),
            'itemsByVendor' => $items->groupBy(fn (CartItem $item) => $item->vendor_id),
            'totals' => $totals,
        ]);
    }
}
