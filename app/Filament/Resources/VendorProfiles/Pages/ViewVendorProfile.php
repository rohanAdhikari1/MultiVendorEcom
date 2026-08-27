<?php

namespace App\Filament\Resources\VendorProfiles\Pages;

use App\Filament\Resources\VendorProfiles\VendorProfileResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewVendorProfile extends ViewRecord
{
    protected static string $resource = VendorProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
