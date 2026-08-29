<?php

namespace App\Filament\Resources\Reviews\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReviewInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Review details')
                    ->schema([
                        TextEntry::make('product.name')
                            ->label('Product'),
                        TextEntry::make('user.name')
                            ->label('Reviewer'),
                        TextEntry::make('rating'),
                        IconEntry::make('is_visible')
                            ->boolean(),
                        TextEntry::make('body')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ])
                    ->columns(2),
                Section::make('Vendor reply')
                    ->schema([
                        TextEntry::make('vendor_reply')
                            ->label('Reply')
                            ->placeholder('No reply yet.')
                            ->columnSpanFull(),
                        TextEntry::make('vendor_replied_at')
                            ->label('Replied at')
                            ->dateTime()
                            ->placeholder('—'),
                    ])
                    ->columns(2),
            ]);
    }
}
