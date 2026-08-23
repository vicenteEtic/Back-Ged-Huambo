<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function ($table) {
            $table->dropConstrainedForeignId('department_id');
        });

        Schema::table('positions', function ($table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('name')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('positions', function ($table) {
            $table->dropConstrainedForeignId('department_id');
        });

        Schema::table('positions', function ($table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('name')
                ->constrained('departments')
                ->cascadeOnDelete();
        });
    }
};
