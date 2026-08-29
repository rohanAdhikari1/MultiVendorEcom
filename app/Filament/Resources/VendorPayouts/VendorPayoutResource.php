<?php

namespace App\Filament\Resources\VendorPayouts;

use App\Filament\Resources\VendorPayouts\Pages\ListVendorPayouts;
use App\Filament\Resources\VendorPayouts\Pages\ViewVendorPayout;
use App\Filament\Resources\VendorPayouts\Schemas\VendorPayoutInfolist;
use App\Filament\Resources\VendorPayouts\Tables\VendorPayoutsTable;
use App\Models\VendorPayout;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class VendorPayoutResource extends Resource
{
    protected static ?string $model = VendorPayout::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Vendors';

    protected static ?string $navigationLabel = 'Payouts';

    public static function infolist(Schema $schema): Schema
    {
        return VendorPayoutInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VendorPayoutsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendorPayouts::route('/'),
            'view' => ViewVendorPayout::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
