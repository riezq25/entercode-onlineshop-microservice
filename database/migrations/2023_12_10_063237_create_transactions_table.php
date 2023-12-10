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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('product_id')->constrained('products');
            $table->unsignedInteger('harga');
            $table->unsignedInteger('jumlah');
            $table->unsignedInteger('total_harga');
            $table->enum('status', ['pending', 'dibayar', 'diproses'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
