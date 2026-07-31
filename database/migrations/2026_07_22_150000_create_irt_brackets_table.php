<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('irt_brackets', function (Blueprint $table) {
            $table->id();
            $table->integer('bracket');
            $table->decimal('min_salary', 12, 2);
            $table->decimal('max_salary', 12, 2);
            $table->decimal('fixed_amount', 12, 2)->default(0);
            $table->decimal('rate', 5, 4)->default(0);
            $table->decimal('excess_over', 12, 2)->default(0);
            $table->boolean('is_exempt')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique('bracket');
        });

        DB::table('irt_brackets')->insert([
            ['bracket' => 1,  'min_salary' => 0,        'max_salary' => 150000,    'fixed_amount' => 0,       'rate' => 0,      'excess_over' => 0,        'is_exempt' => true,  'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['bracket' => 2,  'min_salary' => 150001,   'max_salary' => 200000,    'fixed_amount' => 12500,   'rate' => 0.16,   'excess_over' => 150000,   'is_exempt' => false, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['bracket' => 3,  'min_salary' => 200001,   'max_salary' => 300000,    'fixed_amount' => 31250,   'rate' => 0.18,   'excess_over' => 200000,   'is_exempt' => false, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['bracket' => 4,  'min_salary' => 300001,   'max_salary' => 500000,    'fixed_amount' => 49250,   'rate' => 0.19,   'excess_over' => 300000,   'is_exempt' => false, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['bracket' => 5,  'min_salary' => 500001,   'max_salary' => 1000000,   'fixed_amount' => 87250,   'rate' => 0.20,   'excess_over' => 500000,   'is_exempt' => false, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['bracket' => 6,  'min_salary' => 1000001,  'max_salary' => 1500000,   'fixed_amount' => 187250,  'rate' => 0.21,   'excess_over' => 1000000,  'is_exempt' => false, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['bracket' => 7,  'min_salary' => 1500001,  'max_salary' => 2000000,   'fixed_amount' => 292250,  'rate' => 0.22,   'excess_over' => 1500000,  'is_exempt' => false, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['bracket' => 8,  'min_salary' => 2000001,  'max_salary' => 2500000,   'fixed_amount' => 402250,  'rate' => 0.23,   'excess_over' => 2000000,  'is_exempt' => false, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['bracket' => 9,  'min_salary' => 2500001,  'max_salary' => 5000000,   'fixed_amount' => 517250,  'rate' => 0.24,   'excess_over' => 2500000,  'is_exempt' => false, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['bracket' => 10, 'min_salary' => 5000001,  'max_salary' => 10000000,  'fixed_amount' => 1117250, 'rate' => 0.245,  'excess_over' => 5000000,  'is_exempt' => false, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['bracket' => 11, 'min_salary' => 10000001, 'max_salary' => 999999999, 'fixed_amount' => 2342250, 'rate' => 0.25,   'excess_over' => 10000000, 'is_exempt' => false, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('irt_brackets');
    }
};
