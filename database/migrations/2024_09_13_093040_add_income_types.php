<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddIncomeTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Insert existing income types
        DB::table('income_types')->insert([
            ['name' => 'Salary'],
            ['name' => 'Bonus'],
            ['name' => 'Investment'],
            ['name' => 'Freelance'],
            ['name' => 'Other'],
        
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      
        DB::table('income_types')->whereIn('name', [
            'Salary',
            'Bonus',
            'Investment',
            'Freelance',
            'Other',
        ])->delete();
    }
}
