<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Copia categorias de positions para categories mantendo os mesmos IDs
        //    (assim employees.category continua a apontar correctamente)
        DB::statement("
            INSERT INTO categories (id, name, code, `group`, level, base_salary, description, is_active, created_at, updated_at)
            SELECT id,
                   name,
                   code,
                   CASE WHEN description LIKE 'Categoria: %' THEN TRIM(SUBSTRING(description, 12)) ELSE NULL END,
                   level,
                   base_salary,
                   NULL,
                   is_active,
                   created_at,
                   updated_at
            FROM positions
            WHERE type = 'categoria'
        ");

        // 2. Re-aponta o FK de employees.category para categories
        Schema::table('employees', function ($table) {
            $table->dropConstrainedForeignId('category');
        });

        Schema::table('employees', function ($table) {
            $table->foreignId('category')
                ->nullable()
                ->after('institution_entry_date')
                ->constrained('categories')
                ->nullOnDelete();
        });

        // 3. Remove as categorias de positions — positions passa a conter apenas cargos
        DB::table('positions')->where('type', 'categoria')->delete();
    }

    public function down(): void
    {
        // Repõe as categorias em positions mantendo os mesmos IDs
        DB::statement("
            INSERT INTO positions (id, name, code, `type`, department_id, level, base_salary, description, is_active, created_at, updated_at)
            SELECT c.id,
                   c.name,
                   c.code,
                   'categoria',
                   COALESCE((SELECT id FROM departments ORDER BY id LIMIT 1), 1),
                   c.level,
                   c.base_salary,
                   CASE WHEN c.`group` IS NOT NULL THEN CONCAT('Categoria: ', c.`group`) ELSE NULL END,
                   c.is_active,
                   c.created_at,
                   c.updated_at
            FROM categories c
        ");

        Schema::table('employees', function ($table) {
            $table->dropConstrainedForeignId('category');
        });

        Schema::table('employees', function ($table) {
            $table->foreignId('category')
                ->nullable()
                ->after('institution_entry_date')
                ->constrained('positions')
                ->nullOnDelete();
        });

        DB::table('categories')->delete();
    }
};
