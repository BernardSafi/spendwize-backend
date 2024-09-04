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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('wallet_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('savings_account_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('type', ['income', 'expense', 'transfer', 'exchange']);
            $table->decimal('amount', 15, 2);
            $table->string('currency'); // 'USD' or 'LBP'
            $table->decimal('exchange_rate', 10, 4)->nullable();
            $table->text('description')->nullable();
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
