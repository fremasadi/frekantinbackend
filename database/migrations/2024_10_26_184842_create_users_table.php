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
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // This will create an auto-incrementing unsigned bigint primary key (id)
            $table->string('username')->unique(); // Unique username
            $table->string('email')->unique(); // Unique email
            $table->string('password'); // Password field
            $table->string('phone')->nullable(); // Phone field, nullable
            $table->string('image')->nullable(); // Image field, nullable
            $table->string('role'); // Role field (Admin, Seller, Customer)
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
