<?php

namespace App\Filament\Resources\Coupons\Tables;

use App\Models\Coupon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('value')
                    ->label('Value')
                    ->state(fn (Coupon $record): string => $record->type->value === 'percent'
                        ? number_format((float) $record->value, 2).'%'
                        : 'Rs. '.number_format((float) $record->value, 2))
                    ->sortable(),
                TextColumn::make('usage')
                    ->label('Used')
                    ->state(fn (Coupon $record): string => $record->max_uses !== null
                        ? "{$record->used_count} / {$record->max_uses}"
                        : "{$record->used_count} / \u{221E}"),
                ToggleColumn::make('is_active')
                    ->label('Active'),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
