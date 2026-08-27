<?php

namespace App\Filament\Resources\ContentBlocks\Schemas;

use App\Enums\ContentBlockType;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ContentBlockInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('type')
                    ->badge()
                    ->formatStateUsing(fn (ContentBlockType $state): string => $state->label()),
                TextEntry::make('title')
                    ->placeholder('—'),
                TextEntry::make('subtitle')
                    ->placeholder('—'),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('sort_order')
                    ->numeric(),
                KeyValueEntry::make('payload')
                    ->label('Payload')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
