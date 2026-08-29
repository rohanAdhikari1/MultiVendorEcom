<?php

namespace App\Filament\Resources\VendorPayouts\Schemas;

use App\Enums\PayoutStatus;
use App\Models\VendorPayout;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VendorPayoutInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payout')
                    ->schema([
                        TextEntry::make('vendor.shop_name')
                            ->label('Vendor'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (PayoutStatus $state): string => match ($state) {
                                PayoutStatus::Pending => 'warning',
                                PayoutStatus::Processing => 'info',
                                PayoutStatus::Paid => 'success',
                            })
                            ->formatStateUsing(fn (PayoutStatus $state): string => $state->label()),
                        TextEntry::make('period_start')
                            ->date(),
                        TextEntry::make('period_end')
                            ->date(),
                        TextEntry::make('total_earnings')
                            ->formatStateUsing(fn (string $state): string => 'Rs. '.number_format((float) $state, 2)),
                        TextEntry::make('paid_at')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('notes')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                Section::make('Included items')
                    ->schema([
                        RepeatableEntry::make('orderItems')
                            ->label('')
                            ->schema([
                                TextEntry::make('order.order_number')
                                    ->label('Order #'),
                                TextEntry::make('product_name_snapshot')
                                    ->label('Product'),
                                TextEntry::make('vendor_earning')
                                    ->label('Earning')
                                    ->formatStateUsing(fn (string $state): string => 'Rs. '.number_format((float) $state, 2)),
                            ])
                            ->columns(3),
                    ])
                    ->visible(fn (VendorPayout $record): bool => $record->orderItems->isNotEmpty()),
            ]);
    }
}
