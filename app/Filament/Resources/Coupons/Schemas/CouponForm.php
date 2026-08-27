<?php

namespace App\Filament\Resources\Coupons\Schemas;

use App\Enums\CouponType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        // Select::make('type')->options(CouponType::class) keeps the live
        // form state as the enum instance itself (not its raw string
        // value), so this normalizes before comparing -- same pattern as
        // ContentBlockForm's $isType helper.
        $selectedType = fn (Get $get): ?CouponType => $get('type') instanceof CouponType
            ? $get('type')
            : CouponType::tryFrom((string) $get('type'));

        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, callable $set): void {
                        $set('code', Str::upper(trim((string) $state)));
                    }),
                Select::make('type')
                    ->options(CouponType::class)
                    ->required()
                    ->live(),
                TextInput::make('value')
                    ->label('Value')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->hint(fn (Get $get): string => $selectedType($get) === CouponType::Percent
                        ? 'A percentage between 0 and 100.'
                        : 'A flat amount in NPR.'),
                TextInput::make('min_order_amount')
                    ->label('Minimum order amount')
                    ->numeric()
                    ->minValue(0)
                    ->nullable()
                    ->helperText('Leave blank for no minimum.'),
                TextInput::make('max_uses')
                    ->label('Maximum uses')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->nullable()
                    ->helperText('Leave blank for unlimited uses.'),
                DateTimePicker::make('starts_at')
                    ->label('Starts at')
                    ->nullable(),
                DateTimePicker::make('expires_at')
                    ->label('Expires at')
                    ->nullable(),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
