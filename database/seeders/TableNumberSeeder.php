<?php

namespace Database\Seeders;

use App\Models\TableNumber;
use Illuminate\Database\Seeder;

class TableNumberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Hapus data yang ada terlebih dahulu
        TableNumber::truncate();

        // Buat data nomor meja dari 1 sampai 100
        for ($i = 1; $i <= 100; $i++) {
            TableNumber::create([
                'number' => $i,
                'status' => true,
            ]);
        }

        // Informasi setelah seeding berhasil
        $this->command->info('100 nomor meja berhasil ditambahkan!');
    }
}