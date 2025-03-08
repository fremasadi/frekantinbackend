<?php

// app/Filament/Widgets/OrdersChart.php
namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class OrdersChart extends ChartWidget
{
    protected static ?string $heading = 'Orders Over Time';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $orders = Order::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->groupBy('date')
            ->orderBy('date')
            ->limit(30)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Pesanan Harian',
                    'data' => $orders->pluck('count')->toArray(),
                    'borderColor' => '#36A2EB',
                    'fill' => false,
                ],
            ],
            'labels' => $orders->pluck('date')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
