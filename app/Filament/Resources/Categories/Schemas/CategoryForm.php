<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, callable $set): void {
                        $set('slug', Str::slug((string) $state));
                    }),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('parent_id')
                    ->label('Parent category')
                    ->relationship(
                        name: 'parent',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query, ?Category $record): Builder => $record
                            ? $query->whereKeyNot($record->getKey())
                            : $query,
                    )
                    ->searchable()
                    ->preload()
                    ->placeholder('No parent (top-level)'),
                FileUpload::make('image_path')
                    ->label('Image')
                    ->image()
                    ->disk('public')
                    ->directory('categories'),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }
}
