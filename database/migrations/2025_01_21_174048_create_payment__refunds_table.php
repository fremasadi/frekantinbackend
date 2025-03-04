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
        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
            $table->enum('refund_status', ['PENDING', 'PROCESSED', 'REJECTED']);
            $table->decimal('refund_amount', 15, 2);
            $table->text('refund_reason')->nullable();
            $table->string('refund_proof')->nullable();
            $table->timestamp('refund_processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment__refunds');
    }
};
