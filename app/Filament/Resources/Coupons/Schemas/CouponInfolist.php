<?php

namespace App\Filament\Resources\Coupons\Schemas;

use App\Models\Coupon;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CouponInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('code'),
                TextEntry::make('type')
                    ->badge(),
                TextEntry::make('value')
                    ->state(fn (Coupon $record): string => $record->type->value === 'percent'
                        ? number_format((float) $record->value, 2).'%'
                        : 'Rs. '.number_format((float) $record->value, 2)),
                TextEntry::make('min_order_amount')
                    ->label('Minimum order amount')
                    ->placeholder('—'),
                TextEntry::make('max_uses')
                    ->label('Maximum uses')
                    ->placeholder('Unlimited'),
                TextEntry::make('used_count')
                    ->label('Used'),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('starts_at')
                    ->label('Starts at')
                    ->dateTime()
                    ->placeholder('—'),
                TextEntry::make('expires_at')
                    ->label('Expires at')
                    ->dateTime()
                    ->placeholder('—'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
