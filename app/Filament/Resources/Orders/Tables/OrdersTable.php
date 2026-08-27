<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Customer email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('grand_total')
                    ->formatStateUsing(fn (string $state): string => 'Rs. '.number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->badge()
                    ->formatStateUsing(fn (PaymentMethod $state): string => $state->label()),
                TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (PaymentStatus $state): string => match ($state) {
                        PaymentStatus::Pending => 'warning',
                        PaymentStatus::Paid => 'success',
                        PaymentStatus::Failed => 'danger',
                        PaymentStatus::Refunded => 'gray',
                    })
                    ->formatStateUsing(fn (PaymentStatus $state): string => $state->label()),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (OrderStatus $state): string => match ($state) {
                        OrderStatus::PendingPayment => 'gray',
                        OrderStatus::Processing => 'warning',
                        OrderStatus::PartiallyShipped => 'info',
                        OrderStatus::Shipped => 'primary',
                        OrderStatus::Delivered => 'success',
                        OrderStatus::Cancelled => 'danger',
                    })
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label()),
                TextColumn::make('placed_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('placed_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(OrderStatus::class),
                SelectFilter::make('payment_status')
                    ->options(PaymentStatus::class),
                SelectFilter::make('payment_method')
                    ->options(PaymentMethod::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
