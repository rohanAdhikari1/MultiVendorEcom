<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Shipping details')
                    ->description('Snapshot captured at checkout. Read-only.')
                    ->schema([
                        TextInput::make('shipping_recipient_name')
                            ->label('Recipient')
                            ->required()
                            ->disabled(),
                        TextInput::make('shipping_phone')
                            ->tel()
                            ->required()
                            ->disabled(),
                        TextInput::make('shipping_province')
                            ->required()
                            ->disabled(),
                        TextInput::make('shipping_district')
                            ->required()
                            ->disabled(),
                        TextInput::make('shipping_municipality')
                            ->required()
                            ->disabled(),
                        TextInput::make('shipping_ward_no')
                            ->disabled(),
                        TextInput::make('shipping_street_address')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Totals')
                    ->description('Snapshot captured at checkout. Read-only.')
                    ->schema([
                        TextInput::make('subtotal')
                            ->numeric()
                            ->prefix('Rs.')
                            ->disabled(),
                        TextInput::make('shipping_total')
                            ->numeric()
                            ->prefix('Rs.')
                            ->disabled(),
                        TextInput::make('discount_total')
                            ->numeric()
                            ->prefix('Rs.')
                            ->disabled(),
                        TextInput::make('grand_total')
                            ->numeric()
                            ->prefix('Rs.')
                            ->disabled(),
                    ])
                    ->columns(2),
                Section::make('Manual override')
                    ->description('Manual override — normal status changes happen automatically as vendors fulfill their items. Only change these directly to correct a mistake or handle an edge case.')
                    ->schema([
                        Select::make('status')
                            ->label('Order status')
                            ->options(OrderStatus::class)
                            ->required(),
                        Select::make('payment_status')
                            ->options(PaymentStatus::class)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
