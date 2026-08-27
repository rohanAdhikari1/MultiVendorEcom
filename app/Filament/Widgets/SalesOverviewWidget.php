<?php

namespace App\Filament\Widgets;

use App\Enums\ProductStatus;
use App\Enums\VendorApplicationStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\VendorProfile;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesOverviewWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $ordersToday = Order::query()->whereDate('placed_at', today())->count();

        $gmv = (float) Order::query()->sum('grand_total');

        $pendingVendorApprovals = VendorProfile::query()
            ->where('status', VendorApplicationStatus::Pending)
            ->count();

        $publishedProducts = Product::query()
            ->where('status', ProductStatus::Published)
            ->count();

        return [
            Stat::make('Orders today', (string) $ordersToday)
                ->description('Orders placed today')
                ->icon(Heroicon::OutlinedShoppingBag),
            Stat::make('GMV', 'Rs. '.number_format($gmv, 2))
                ->description('Gross merchandise value, all time')
                ->icon(Heroicon::OutlinedBanknotes),
            Stat::make('Pending vendor approvals', (string) $pendingVendorApprovals)
                ->description('Awaiting admin review')
                ->icon(Heroicon::OutlinedBuildingStorefront),
            Stat::make('Published products', (string) $publishedProducts)
                ->description('Currently live in the catalog')
                ->icon(Heroicon::OutlinedTag),
        ];
    }
}
