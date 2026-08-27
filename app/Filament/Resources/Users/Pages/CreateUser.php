<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * `is_active` is intentionally excluded from {@see User}'s mass
     * assignment allow-list, so it has to be set directly here rather than
     * via the model's `create()`/`update()` mass assignment helpers.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $isActive = (bool) Arr::pull($data, 'is_active', true);

        /** @var User $record */
        $record = $this->getModel()::create($data);
        $record->is_active = $isActive;
        $record->save();

        return $record;
    }
}
