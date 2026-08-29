@php
    use App\Enums\PayoutStatus;
    use Illuminate\Support\Number;
@endphp

<div class="px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Earnings</h1>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-lg border border-gray-200 bg-white p-5 transition-shadow hover:shadow-md">
            <p class="text-sm text-gray-500">Total lifetime earnings</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">
                <x-ui.stat-counter :value="$totalLifetimeEarnings" prefix="NPR " :decimals="2" />
            </p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-5 transition-shadow hover:shadow-md">
            <p class="text-sm text-gray-500">Paid out</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">
                <x-ui.stat-counter :value="$totalPaidOut" prefix="NPR " :decimals="2" />
            </p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-5 transition-shadow hover:shadow-md">
            <p class="text-sm text-gray-500">Pending payout</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">
                <x-ui.stat-counter :value="$pendingPayout" prefix="NPR " :decimals="2" />
            </p>
        </div>
    </div>

    <div class="mt-8">
        <h2 class="text-lg font-semibold text-gray-900">Payout history</h2>

        @if ($payouts->isEmpty())
            <div class="mt-4">
                <x-ui.empty-state title="No payouts yet" message="Payout batches generated for your delivered orders will show up here." />
            </div>
        @else
            <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Period</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Amount</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Paid on</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach ($payouts as $payout)
                            @php
                                $variant = match ($payout->status) {
                                    PayoutStatus::Paid => 'success',
                                    PayoutStatus::Processing => 'primary',
                                    PayoutStatus::Pending => 'warning',
                                };
                            @endphp
                            <tr wire:key="payout-{{ $payout->id }}">
                                <td class="px-4 py-3 text-gray-600">{{ $payout->period_start->format('M j, Y') }} &ndash; {{ $payout->period_end->format('M j, Y') }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ Number::currency((float) $payout->total_earnings, 'NPR') }}</td>
                                <td class="px-4 py-3"><x-ui.badge :variant="$variant">{{ $payout->status->label() }}</x-ui.badge></td>
                                <td class="px-4 py-3 text-gray-600">{{ $payout->paid_at?->format('M j, Y') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
