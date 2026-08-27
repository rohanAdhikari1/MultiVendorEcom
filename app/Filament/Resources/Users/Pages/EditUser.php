<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * `is_active` is intentionally excluded from {@see User}'s mass
     * assignment allow-list, so it has to be set directly here rather than
     * via the model's `create()`/`update()` mass assignment helpers.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        $isActive = (bool) Arr::pull($data, 'is_active', $record->is_active);

        $record->update($data);
        $record->is_active = $isActive;
        $record->save();

        return $record;
    }
}
