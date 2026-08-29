<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\VendorSubOrderStatus;
use App\Models\Order;
use App\Notifications\OrderStatusUpdated;

/**
 * Derives an Order's overall fulfillment status from the vendor_status of
 * each of its OrderItems. Triggered whenever a vendor updates a line item's
 * fulfillment status (see Vendor\OrderManager). Never touches
 * Order::payment_status — that's a separate axis owned by the payment flow.
 *
 * Cancelled line items are excluded from the "active" set before deriving
 * the aggregate status, unless every single item is cancelled (in which
 * case the whole order is Cancelled) — a stray cancelled line in an
 * otherwise-fulfilled multi-vendor order shouldn't block the order from
 * reading as Shipped/Delivered.
 */
class RecalculateOrderStatusAction
{
    public function execute(Order $order): Order
    {
        $statuses = $order->items()->pluck('vendor_status');

        if ($statuses->isEmpty()) {
            return $order;
        }

        if ($statuses->every(fn (VendorSubOrderStatus $status) => $status === VendorSubOrderStatus::Cancelled)) {
            $newStatus = OrderStatus::Cancelled;
        } else {
            $active = $statuses->reject(fn (VendorSubOrderStatus $status) => $status === VendorSubOrderStatus::Cancelled);

            $newStatus = match (true) {
                $active->every(fn (VendorSubOrderStatus $status) => $status === VendorSubOrderStatus::Delivered) => OrderStatus::Delivered,
                $active->every(fn (VendorSubOrderStatus $status) => in_array($status, [VendorSubOrderStatus::Shipped, VendorSubOrderStatus::Delivered], true)) => OrderStatus::Shipped,
                $active->contains(fn (VendorSubOrderStatus $status) => in_array($status, [VendorSubOrderStatus::Shipped, VendorSubOrderStatus::Delivered], true)) => OrderStatus::PartiallyShipped,
                default => OrderStatus::Processing,
            };
        }

        if ($order->status !== $newStatus) {
            $order->status = $newStatus;
            $order->save();

            // Only an actual transition into Shipped/Delivered notifies the
            // customer -- never fired on a no-op recalculation that leaves
            // status unchanged, and never for any other status value.
            if (in_array($newStatus, [OrderStatus::Shipped, OrderStatus::Delivered], true)) {
                $order->user->notify(new OrderStatusUpdated($order, $newStatus));
            }
        }

        return $order;
    }
}
