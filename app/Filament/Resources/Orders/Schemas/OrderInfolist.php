<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentTransactionStatus;
use App\Enums\VendorSubOrderStatus;
use App\Models\Order;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order')
                    ->schema([
                        TextEntry::make('order_number'),
                        TextEntry::make('user.name')
                            ->label('Customer'),
                        TextEntry::make('user.email')
                            ->label('Customer email'),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (OrderStatus $state): string => $state->label()),
                        TextEntry::make('payment_status')
                            ->badge()
                            ->formatStateUsing(fn (PaymentStatus $state): string => $state->label()),
                        TextEntry::make('payment_method')
                            ->badge()
                            ->formatStateUsing(fn (PaymentMethod $state): string => $state->label()),
                        TextEntry::make('placed_at')
                            ->dateTime()
                            ->placeholder('—'),
                    ])
                    ->columns(3),
                Section::make('Shipping details')
                    ->schema([
                        TextEntry::make('shipping_recipient_name')
                            ->label('Recipient'),
                        TextEntry::make('shipping_phone')
                            ->label('Phone'),
                        TextEntry::make('shipping_province'),
                        TextEntry::make('shipping_district'),
                        TextEntry::make('shipping_municipality'),
                        TextEntry::make('shipping_ward_no')
                            ->label('Ward no.')
                            ->placeholder('—'),
                        TextEntry::make('shipping_street_address')
                            ->label('Street address')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                Section::make('Totals')
                    ->schema([
                        TextEntry::make('subtotal')
                            ->formatStateUsing(fn (string $state): string => 'Rs. '.number_format((float) $state, 2)),
                        TextEntry::make('shipping_total')
                            ->formatStateUsing(fn (string $state): string => 'Rs. '.number_format((float) $state, 2)),
                        TextEntry::make('discount_total')
                            ->formatStateUsing(fn (string $state): string => 'Rs. '.number_format((float) $state, 2)),
                        TextEntry::make('grand_total')
                            ->formatStateUsing(fn (string $state): string => 'Rs. '.number_format((float) $state, 2)),
                    ])
                    ->columns(4),
                Section::make('Line items')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                TextEntry::make('vendor.shop_name')
                                    ->label('Vendor'),
                                TextEntry::make('product_name_snapshot')
                                    ->label('Product'),
                                TextEntry::make('quantity'),
                                TextEntry::make('unit_price_snapshot')
                                    ->label('Unit price')
                                    ->formatStateUsing(fn (string $state): string => 'Rs. '.number_format((float) $state, 2)),
                                TextEntry::make('line_total')
                                    ->formatStateUsing(fn (string $state): string => 'Rs. '.number_format((float) $state, 2)),
                                TextEntry::make('vendor_status')
                                    ->label('Fulfillment status')
                                    ->badge()
                                    ->formatStateUsing(fn (VendorSubOrderStatus $state): string => $state->label()),
                            ])
                            ->columns(6),
                    ])
                    ->visible(fn (Order $record): bool => $record->items->isNotEmpty()),
                Section::make('Payment transactions')
                    ->schema([
                        RepeatableEntry::make('paymentTransactions')
                            ->label('')
                            ->schema([
                                TextEntry::make('gateway')
                                    ->formatStateUsing(fn (PaymentMethod $state): string => $state->label()),
                                TextEntry::make('gateway_reference')
                                    ->label('Reference')
                                    ->placeholder('—'),
                                TextEntry::make('amount')
                                    ->formatStateUsing(fn (string $state): string => 'Rs. '.number_format((float) $state, 2)),
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn (PaymentTransactionStatus $state): string => $state->label()),
                                TextEntry::make('verified_at')
                                    ->dateTime()
                                    ->placeholder('—'),
                            ])
                            ->columns(5),
                    ])
                    ->visible(fn (Order $record): bool => $record->paymentTransactions->isNotEmpty()),
            ]);
    }
}
