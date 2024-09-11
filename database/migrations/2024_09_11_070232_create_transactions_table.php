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
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['income', 'expense', 'transfer', 'exchange']);
            $table->string('subtype')->nullable();
            $table->decimal('amount', 15, 2);
            $table->enum('currency', ['USD', 'LBP']);
            $table->string('from_account')->nullable();
            $table->string('to_account')->nullable();
            $table->decimal('exchange_rate', 15, 6)->nullable();
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
