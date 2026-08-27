<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ImageEntry::make('image_path')
                    ->label('Image')
                    ->disk('public')
                    ->placeholder('—'),
                TextEntry::make('name'),
                TextEntry::make('slug'),
                TextEntry::make('parent.name')
                    ->label('Parent category')
                    ->placeholder('—'),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('sort_order')
                    ->numeric(),
                TextEntry::make('products_count')
                    ->label('Products')
                    ->state(fn (Category $record): int => $record->products()->count()),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
