<?php

namespace App\Filament\Resources\VendorProfiles\Tables;

use App\Enums\VendorApplicationStatus;
use App\Models\VendorProfile;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VendorProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('shop_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Owner')
                    ->searchable(['name'])
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Owner email')
                    ->searchable(['email'])
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (VendorApplicationStatus $state): string => match ($state) {
                        VendorApplicationStatus::Pending => 'warning',
                        VendorApplicationStatus::Approved => 'success',
                        VendorApplicationStatus::Rejected, VendorApplicationStatus::Suspended => 'danger',
                    })
                    ->formatStateUsing(fn (VendorApplicationStatus $state): string => $state->label()),
                TextColumn::make('commission_override_percent')
                    ->label('Commission override')
                    ->numeric(2)
                    ->suffix('%')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(VendorApplicationStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('approve')
                    ->label('Approve')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (VendorProfile $record): bool => $record->status === VendorApplicationStatus::Pending)
                    ->requiresConfirmation()
                    ->modalDescription('Approve this vendor application? The vendor will be able to publish products.')
                    ->action(function (VendorProfile $record): void {
                        $record->update([
                            'status' => VendorApplicationStatus::Approved,
                            'approved_at' => now(),
                        ]);
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (VendorProfile $record): bool => $record->status === VendorApplicationStatus::Pending)
                    ->schema([
                        Textarea::make('rejected_reason')
                            ->label('Rejection reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (VendorProfile $record, array $data): void {
                        $record->update([
                            'status' => VendorApplicationStatus::Rejected,
                            'rejected_reason' => $data['rejected_reason'],
                        ]);
                    }),
                Action::make('suspend')
                    ->label('Suspend')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->visible(fn (VendorProfile $record): bool => $record->status === VendorApplicationStatus::Approved)
                    ->requiresConfirmation()
                    ->modalDescription('Suspend this vendor? Their products will remain but they should be prevented from further activity in a later phase.')
                    ->action(fn (VendorProfile $record) => $record->update([
                        'status' => VendorApplicationStatus::Suspended,
                    ])),
            ]);
    }
}
