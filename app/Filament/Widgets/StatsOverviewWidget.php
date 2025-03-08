<?php
// app/Filament/Widgets/StatsOverviewWidget.php
namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends \Filament\Widgets\StatsOverviewWidget 
{
    protected function getStats(): array
    {
        // Get total users by role
        $customerCount = User::where('role', 'customer')->count();
        $sellerCount = User::where('role', 'seller')->count();

        // Get orders statistics
        $totalOrders = Order::count();
        $totalRevenue = Order::sum('total_amount');
        $averageOrderValue = Order::avg('total_amount');

        // Get recent order status distribution
        $pendingOrders = Order::where('order_status', 'pending')->count();
        $processingOrders = Order::where('order_status', 'processing')->count();
        $completedOrders = Order::where('order_status', 'completed')->count();

        return [
            Stat::make('Total Pembeli', $customerCount)
                ->description('Jumlah pembeli terdaftar')
                ->icon('heroicon-o-users')
                ->color('success'),

            Stat::make('Total Penjual', $sellerCount)
                ->description('Jumlah penjual terdaftar')
                ->icon('heroicon-o-shopping-bag')
                ->color('success'),

            Stat::make('Total Pesanan', $totalOrders)
                ->description('Semua Pesanan')
                ->icon('heroicon-o-shopping-cart')
                ->color('primary'),

            Stat::make('Total Pendapatan', 'Rp ' . number_format($totalRevenue, 0, ',', '.'))
                ->description('Semua Pendapatan')
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),

            Stat::make('Jumlah pesanan rata-rata', 'Rp ' . number_format($averageOrderValue, 0, ',', '.'))
                ->description('Harga pesanan rata-rata')
                ->icon('heroicon-o-calculator')
                ->color('warning'),

            // Stat::make('Order Status', "{$pendingOrders} Pending | {$processingOrders} Processing | {$completedOrders} Completed")
            //     ->description('Current order distribution')
            //     ->icon('heroicon-o-chart-pie')
            //     ->color('danger'),
        ];
    }
}

