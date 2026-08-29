<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Number;

/**
 * Sent to the customer when an online gateway (eSewa/Khalti/Fonepay)
 * authoritatively confirms payment for their order — see
 * PaymentCallbackController. COD orders never go through a gateway
 * callback, so they never trigger this notification.
 */
class OrderPaymentConfirmed extends Notification
{
    public function __construct(
        public readonly Order $order,
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
            ->subject("Payment confirmed — {$this->order->order_number}")
            ->greeting("Hello {$notifiable->name},")
            ->line("We've received your payment for order {$this->order->order_number}.")
            ->line('Amount paid: '.Number::currency((float) $this->order->grand_total, $this->order->currency))
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
            'message' => "Payment confirmed for order {$this->order->order_number} — ".Number::currency((float) $this->order->grand_total, $this->order->currency).' paid.',
            'url' => route('orders.confirmation', $this->order->order_number),
        ];
    }
}
