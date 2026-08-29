<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the customer when RecalculateOrderStatusAction detects an actual
 * transition of Order::status into Shipped or Delivered (never on a no-op
 * recalculation that leaves the status unchanged — see the guard in that
 * action).
 */
class OrderStatusUpdated extends Notification
{
    public function __construct(
        public readonly Order $order,
        public readonly OrderStatus $status,
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
        return (new MailMessage)
            ->subject("Order {$this->order->order_number} is now {$this->status->label()}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your order {$this->order->order_number} is now {$this->status->label()}.")
            ->action('View Order', route('orders.confirmation', $this->order->order_number))
            ->line('Thank you for shopping with us!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'order_number' => $this->order->order_number,
            'status' => $this->status->value,
            'message' => "Order {$this->order->order_number} is now {$this->status->label()}.",
            'url' => route('orders.confirmation', $this->order->order_number),
        ];
    }
}
