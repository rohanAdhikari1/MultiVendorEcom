<?php

namespace App\Filament\Resources\VendorPayouts\Pages;

use App\Filament\Resources\VendorPayouts\VendorPayoutResource;
use Filament\Resources\Pages\ListRecords;

class ListVendorPayouts extends ListRecords
{
    protected static string $resource = VendorPayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
