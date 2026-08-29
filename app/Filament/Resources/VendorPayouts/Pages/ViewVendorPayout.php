<?php

namespace App\Filament\Resources\VendorPayouts\Pages;

use App\Enums\PayoutStatus;
use App\Filament\Resources\VendorPayouts\VendorPayoutResource;
use App\Models\VendorPayout;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewVendorPayout extends ViewRecord
{
    protected static string $resource = VendorPayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markPaid')
                ->label('Mark Paid')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn (): bool => $this->payoutRecord()->status !== PayoutStatus::Paid)
                ->requiresConfirmation()
                ->modalDescription('Mark this payout as paid? This should only be done once funds have actually been transferred to the vendor.')
                ->action(function (): void {
                    $this->payoutRecord()->update([
                        'status' => PayoutStatus::Paid,
                        'paid_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Payout marked as paid')
                        ->success()
                        ->send();
                }),
        ];
    }

    private function payoutRecord(): VendorPayout
    {
        /** @var VendorPayout $record */
        $record = $this->getRecord();

        return $record;
    }
}
