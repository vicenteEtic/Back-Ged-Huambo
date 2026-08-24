<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Uma área NÃO pertence a um departamento — é o departamento que
        // pertence a uma área (1 área : N departamentos)
        Schema::table('areas', function ($table) {
            $table->dropConstrainedForeignId('department_id');
        });

        Schema::table('departments', function ($table) {
            $table->foreignId('area_id')
                ->nullable()
                ->after('parent_id')
                ->constrained('areas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('departments', function ($table) {
            $table->dropConstrainedForeignId('area_id');
        });

        Schema::table('areas', function ($table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('id')
                ->constrained('departments')
                ->nullOnDelete();
        });
    }
};
