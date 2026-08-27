<?php

namespace App\Filament\Resources\VendorProfiles\Schemas;

use App\Enums\VendorApplicationStatus;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VendorProfileInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Shop details')
                    ->schema([
                        TextEntry::make('shop_name'),
                        TextEntry::make('shop_slug'),
                        TextEntry::make('user.name')
                            ->label('Owner'),
                        TextEntry::make('user.email')
                            ->label('Owner email'),
                        TextEntry::make('description')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        ImageEntry::make('logo_path')
                            ->label('Logo')
                            ->disk('public')
                            ->placeholder('—'),
                        ImageEntry::make('banner_path')
                            ->label('Banner')
                            ->disk('public')
                            ->placeholder('—'),
                    ])
                    ->columns(2),
                Section::make('Moderation')
                    ->schema([
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (VendorApplicationStatus $state): string => match ($state) {
                                VendorApplicationStatus::Pending => 'warning',
                                VendorApplicationStatus::Approved => 'success',
                                VendorApplicationStatus::Rejected, VendorApplicationStatus::Suspended => 'danger',
                            })
                            ->formatStateUsing(fn (VendorApplicationStatus $state): string => $state->label()),
                        TextEntry::make('commission_override_percent')
                            ->label('Commission override')
                            ->suffix('%')
                            ->placeholder('Uses platform default'),
                        TextEntry::make('approved_at')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('rejected_reason')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
