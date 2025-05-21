<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\SellerEarning;
use Carbon\Carbon;

class BackfillSellerEarnings extends Command
{
    protected $signature = 'backfill:seller-earnings';
    protected $description = 'Rekap pendapatan bulanan seller dari orders yang sudah selesai';

    public function handle()
    {
        $this->info('⏳ Starting backfill process...');

        // Ambil hanya orders yang selesai
        $orders = Order::whereIn('order_status', ['completed', 'delivered'])->get();

        if ($orders->isEmpty()) {
            $this->warn('⚠️ Tidak ada order yang bisa direkap.');
            return;
        }

        // Group berdasarkan seller_id dan bulan (YYYY-MM-01)
        $grouped = $orders->groupBy(function ($order) {
            $month = Carbon::parse($order->created_at)->startOfMonth()->toDateString();
            return $order->seller_id . '|' . $month;
        });

        $bar = $this->output->createProgressBar(count($grouped));
        $bar->start();

        foreach ($grouped as $key => $group) {
            [$sellerId, $monthDate] = explode('|', $key);
            $total = $group->sum('total_amount');

            SellerEarning::updateOrCreate(
                [
                    'seller_id' => $sellerId,
                    'month' => $monthDate,
                ],
                [
                    'total_income' => $total,
                    // 'status' => 'unpaid',
                ]
            );

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('✅ Backfill selesai!');
    }
}
