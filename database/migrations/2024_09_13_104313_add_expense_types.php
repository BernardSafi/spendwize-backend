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
        DB::table('expense_types')->insert([
            ['name' => 'Groceries'],
            ['name' => 'Rent'],
            ['name' => 'Bills'],
            ['name' => 'Transportation'],
            ['name' => 'Healthcare'],
            ['name' => 'Entertainment'],
            ['name' => 'Clothing'],
            ['name' => 'Education'],
            ['name' => 'Travel'],
            ['name' => 'Personal Care'],
            ['name' => 'Insurance'],
            ['name' => 'Other'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('expense_types')->whereIn('name', [
            'Groceries',
            'Rent',
            'Bills',
            'Transportation',
            'Healthcare',
            'Entertainment',
            'Clothing',
            'Education',
            'Travel',
            'Personal Care',
            'Insurance',
            'Other',
        ])->delete();
    }
};
