<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class SalesTrendWidget extends ChartWidget
{
    protected ?string $heading = 'Sales trend (last 30 days)';

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $days = collect(range(0, 29))
            ->map(fn (int $offset): string => now()->subDays(29 - $offset)->toDateString());

        // Single grouped aggregate query — not one query per data point.
        $totalsByDay = Order::query()
            ->selectRaw('DATE(placed_at) as day, SUM(grand_total) as total')
            ->whereNotNull('placed_at')
            ->where('placed_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('day')
            ->pluck('total', 'day');

        return [
            'datasets' => [
                [
                    'label' => 'GMV (Rs.)',
                    'data' => $days->map(fn (string $day): float => (float) ($totalsByDay[$day] ?? 0))->values()->all(),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $days->map(fn (string $day): string => Carbon::parse($day)->format('M j'))->values()->all(),
        ];
    }
}
