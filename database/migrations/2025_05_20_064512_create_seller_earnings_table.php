<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('seller_earnings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
        $table->date('month'); // format: YYYY-MM-01
        $table->decimal('total_income', 15, 2)->default(0);
        $table->enum('status', ['unpaid', 'paid'])->default('unpaid');
        $table->timestamp('paid_at')->nullable();
        $table->timestamps();

        $table->unique(['seller_id', 'month']); // tiap seller hanya 1 rekap per bulan
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_earnings');
    }
};
