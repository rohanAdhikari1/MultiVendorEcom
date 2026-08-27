<?php

use App\Actions\Checkout\CalculateOrderTotalsAction;
use App\Models\CartItem;
use Illuminate\Support\Collection;

test('it sums line totals and applies the flat shipping fee for a non-empty cart', function () {
    $items = new Collection([
        new CartItem(['price_snapshot' => 250, 'quantity' => 3]),
        new CartItem(['price_snapshot' => 100, 'quantity' => 1]),
    ]);

    $totals = (new CalculateOrderTotalsAction)->execute($items);

    expect($totals['subtotal'])->toBe(850.0);
    expect($totals['shipping_total'])->toBe(CalculateOrderTotalsAction::FLAT_SHIPPING_TOTAL);
    expect($totals['discount_total'])->toBe(0.0);
    expect($totals['grand_total'])->toBe(950.0);
});

test('an empty cart has zero totals and no shipping charge', function () {
    $totals = (new CalculateOrderTotalsAction)->execute(new Collection);

    expect($totals['subtotal'])->toBe(0.0);
    expect($totals['shipping_total'])->toBe(0.0);
    expect($totals['grand_total'])->toBe(0.0);
});
