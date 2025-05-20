<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixPendingPaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update semua payment_status 'PENDING' menjadi 'FAILED'
        DB::table('payments')
            ->where('payment_status', 'PENDING')
            ->update(['payment_status' => 'FAILED']);

        // Optional: tampilkan jumlah data yang terupdate
        $count = DB::table('payments')
            ->where('payment_status', 'FAILED')
            ->count();

        $this->command->info("Updated $count payments to FAILED.");
    }
}
