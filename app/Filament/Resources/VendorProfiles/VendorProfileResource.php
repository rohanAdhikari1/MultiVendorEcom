<?php

namespace App\Filament\Resources\VendorProfiles;

use App\Filament\Resources\VendorProfiles\Pages\EditVendorProfile;
use App\Filament\Resources\VendorProfiles\Pages\ListVendorProfiles;
use App\Filament\Resources\VendorProfiles\Pages\ViewVendorProfile;
use App\Filament\Resources\VendorProfiles\Schemas\VendorProfileForm;
use App\Filament\Resources\VendorProfiles\Schemas\VendorProfileInfolist;
use App\Filament\Resources\VendorProfiles\Tables\VendorProfilesTable;
use App\Models\VendorProfile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class VendorProfileResource extends Resource
{
    protected static ?string $model = VendorProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Vendors';

    protected static ?string $navigationLabel = 'Vendors';

    protected static ?string $recordTitleAttribute = 'shop_name';

    public static function form(Schema $schema): Schema
    {
        return VendorProfileForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return VendorProfileInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VendorProfilesTable::configure($table);
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
            'index' => ListVendorProfiles::route('/'),
            'view' => ViewVendorProfile::route('/{record}'),
            'edit' => EditVendorProfile::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
