<?php

namespace App\Filament\Resources\VendorProfiles\Tables;

use App\Actions\Payouts\CreateVendorPayoutBatchAction;
use App\Enums\VendorApplicationStatus;
use App\Models\VendorProfile;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

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
                Action::make('generatePayout')
                    ->label('Generate Payout')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->color('primary')
                    ->visible(fn (VendorProfile $record): bool => $record->status === VendorApplicationStatus::Approved)
                    ->schema([
                        DatePicker::make('period_start')
                            ->label('Period start')
                            ->native(false)
                            ->helperText('Leave blank to include everything eligible to date.'),
                        DatePicker::make('period_end')
                            ->label('Period end')
                            ->native(false)
                            ->helperText('Leave blank to include everything eligible to date.'),
                    ])
                    ->modalDescription('Batches every Delivered item not yet in a payout into a new payout for this vendor.')
                    ->action(function (VendorProfile $record, array $data): void {
                        $payout = app(CreateVendorPayoutBatchAction::class)->execute(
                            $record,
                            filled($data['period_start'] ?? null) ? Carbon::parse($data['period_start'])->startOfDay() : null,
                            filled($data['period_end'] ?? null) ? Carbon::parse($data['period_end'])->endOfDay() : null,
                        );

                        if ($payout === null) {
                            Notification::make()
                                ->title('Nothing to pay out')
                                ->body('This vendor has no delivered items awaiting payout for the selected period.')
                                ->warning()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Payout batch generated')
                            ->body('Rs. '.number_format((float) $payout->total_earnings, 2).' across the eligible items.')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
