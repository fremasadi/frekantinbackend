<?php

// app/Filament/Widgets/RevenueChart.php
namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Daily Revenue';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $revenue = Order::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total_amount) as total')
        )
            ->groupBy('date')
            ->orderBy('date')
            ->limit(30)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Pendapatan Harian',
                    'data' => $revenue->pluck('total')->toArray(),
                    'borderColor' => '#4BC0C0',
                    'fill' => false,
                ],
            ],
            'labels' => $revenue->pluck('date')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}