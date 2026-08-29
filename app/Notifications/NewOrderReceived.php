<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Number;

/**
 * Sent to a vendor's User the moment PlaceOrderAction commits an order that
 * contains at least one of their items. Deliberately scoped to just that
 * vendor's own line items and earning — a multi-vendor order never leaks
 * another vendor's items/earning into this notification.
 */
class NewOrderReceived extends Notification
{
    public function __construct(
        public readonly Order $order,
        public readonly VendorProfile $vendor,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $itemCount = $this->vendorItems()->sum('quantity');

        return (new MailMessage)
            ->subject("New order received — {$this->order->order_number}")
            ->greeting("Hello {$notifiable->name},")
            ->line("You've received a new order ({$this->order->order_number}) containing {$itemCount} item(s) from your shop.")
            ->line('Your earning for this order: '.Number::currency($this->vendorEarning(), $this->order->currency))
            ->action('View Orders', route('vendor.orders.index'))
            ->line('Thank you for selling with us!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        $itemCount = $this->vendorItems()->sum('quantity');

        return [
            'order_number' => $this->order->order_number,
            'message' => "New order {$this->order->order_number} received — {$itemCount} item(s), ".Number::currency($this->vendorEarning(), $this->order->currency).' earning.',
            'url' => route('vendor.orders.index'),
        ];
    }

    /**
     * @return Collection<int, OrderItem>
     */
    protected function vendorItems(): Collection
    {
        return $this->order->items->where('vendor_id', $this->vendor->id);
    }

    protected function vendorEarning(): float
    {
        return (float) $this->vendorItems()->sum(fn ($item) => (float) $item->vendor_earning);
    }
}
