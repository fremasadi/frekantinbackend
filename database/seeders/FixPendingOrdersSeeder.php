<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;

class FixPendingOrdersSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('orders')
            ->where('order_status', 'PENDING')
            ->update(['order_status' => 'CANCELLED']);

        // Optional log
        $count = DB::table('orders')
            ->where('order_status', 'CANCELLED')
            ->count();

        $this->command->info("Updated $count orders to CANCELLED.");
    }
}
