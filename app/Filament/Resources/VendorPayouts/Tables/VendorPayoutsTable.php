<?php

namespace App\Filament\Resources\VendorPayouts\Tables;

use App\Enums\PayoutStatus;
use App\Models\VendorPayout;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VendorPayoutsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('vendor.shop_name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('period_start')
                    ->date()
                    ->sortable(),
                TextColumn::make('period_end')
                    ->date()
                    ->sortable(),
                TextColumn::make('total_earnings')
                    ->formatStateUsing(fn (string $state): string => 'Rs. '.number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (PayoutStatus $state): string => match ($state) {
                        PayoutStatus::Pending => 'warning',
                        PayoutStatus::Processing => 'info',
                        PayoutStatus::Paid => 'success',
                    })
                    ->formatStateUsing(fn (PayoutStatus $state): string => $state->label()),
                TextColumn::make('paid_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('vendor_id')
                    ->label('Vendor')
                    ->relationship('vendor', 'shop_name')
                    ->searchable(),
                SelectFilter::make('status')
                    ->options(PayoutStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('markPaid')
                    ->label('Mark Paid')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (VendorPayout $record): bool => $record->status !== PayoutStatus::Paid)
                    ->requiresConfirmation()
                    ->modalDescription('Mark this payout as paid? This should only be done once funds have actually been transferred to the vendor.')
                    ->action(function (VendorPayout $record): void {
                        $record->update([
                            'status' => PayoutStatus::Paid,
                            'paid_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Payout marked as paid')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
