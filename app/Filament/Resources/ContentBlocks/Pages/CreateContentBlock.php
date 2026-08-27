<?php

namespace App\Filament\Resources\ContentBlocks\Pages;

use App\Filament\Resources\ContentBlocks\ContentBlockResource;
use App\Filament\Resources\ContentBlocks\Schemas\ContentBlockPayloadMapper;
use Filament\Resources\Pages\CreateRecord;

class CreateContentBlock extends CreateRecord
{
    protected static string $resource = ContentBlockResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return ContentBlockPayloadMapper::pack($data);
    }
}
