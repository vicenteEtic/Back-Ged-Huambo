<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = [
        'departments' => 'departments_responsible_id_foreign',
        'areas' => 'areas_responsible_id_foreign',
    ];

    /**
     * Departamentos/Áreas passam a apontar `responsible_id` para funcionários
     * (employees) em vez de utilizadores (users). Os valores existentes são
     * convertidos automaticamente: user_id → employee correspondente.
     */
    public function up(): void
    {
        foreach (self::TABLES as $table => $fkName) {
            DB::statement("
                UPDATE {$table} t
                SET t.responsible_id = (
                    SELECT e.id
                    FROM employees e
                    WHERE e.user_id = t.responsible_id
                    ORDER BY e.id
                    LIMIT 1
                )
                WHERE t.responsible_id IS NOT NULL
                  AND EXISTS (SELECT 1 FROM employees e WHERE e.user_id = t.responsible_id)
            ");

            Schema::table($table, function (Blueprint $blueprint) use ($fkName) {
                $blueprint->dropForeign($fkName);
            });
        }

        // Re-criar as FKs apontando para employees
        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('responsible_id')->references('id')->on('employees')->nullOnDelete();
        });

        Schema::table('areas', function (Blueprint $table) {
            $table->foreign('responsible_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Reverter: employee_id → user_id (quando tiver user associado)
        foreach (array_keys(self::TABLES) as $table) {
            DB::statement("
                UPDATE {$table} t
                SET t.responsible_id = (
                    SELECT e.user_id
                    FROM employees e
                    WHERE e.id = t.responsible_id
                      AND e.user_id IS NOT NULL
                    LIMIT 1
                )
                WHERE t.responsible_id IS NOT NULL
                  AND EXISTS (SELECT 1 FROM employees e WHERE e.id = t.responsible_id AND e.user_id IS NOT NULL)
            ");

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropForeign(self::TABLES[$table]);
            });
        }

        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('responsible_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('areas', function (Blueprint $table) {
            $table->foreign('responsible_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
