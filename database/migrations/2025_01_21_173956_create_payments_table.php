<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->enum('payment_status', ['PENDING', 'PAID', 'FAILED', 'EXPIRED', 'REFUNDED']);
            $table->enum('payment_type', ['CASH', 'QRIS', 'BANK_TRANSFER', 'E_WALLET']);
            $table->string('payment_gateway');
            $table->string('payment_gateway_reference_id');
            $table->json('payment_gateway_response');
            $table->decimal('gross_amount', 15, 2);
            $table->string('payment_proof')->nullable();
            $table->timestamp('payment_date')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
