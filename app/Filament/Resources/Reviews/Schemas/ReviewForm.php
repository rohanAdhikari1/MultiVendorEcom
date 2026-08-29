<?php

namespace App\Filament\Resources\Reviews\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Review details')
                    ->description('Reviews originate from customers on the storefront, and replies are written by the vendor from their dashboard. Shown here for context only.')
                    ->schema([
                        Select::make('product_id')
                            ->relationship('product', 'name')
                            ->label('Product')
                            ->disabled(),
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->label('Reviewer')
                            ->disabled(),
                        TextInput::make('rating')
                            ->numeric()
                            ->disabled(),
                        Textarea::make('body')
                            ->disabled()
                            ->columnSpanFull(),
                        Textarea::make('vendor_reply')
                            ->label('Vendor reply')
                            ->placeholder('No reply yet.')
                            ->disabled()
                            ->columnSpanFull(),
                        DateTimePicker::make('vendor_replied_at')
                            ->label('Replied at')
                            ->disabled(),
                    ])
                    ->columns(2),
                Section::make('Moderation')
                    ->description('This is the only field an admin may change from this panel.')
                    ->schema([
                        Toggle::make('is_visible'),
                    ]),
            ]);
    }
}
