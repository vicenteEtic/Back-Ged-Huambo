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
    /**
     * Verifica se a FK existe. O information_schema é específico do MySQL;
     * em SQLite (testes) as FKs não são impostas, logo não há nada a remover.
     */
    private function foreignKeyExists(string $table, string $fkName): bool
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return false;
        }

        return (bool) DB::select(
            "SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$table, $fkName]
        );
    }

    public function up(): void
    {
        // 1. Remover as FKs atuais (apontam para users) antes de converter os dados
        foreach (self::TABLES as $table => $fkName) {
            if ($this->foreignKeyExists($table, $fkName)) {
                Schema::table($table, function (Blueprint $blueprint) use ($fkName) {
                    $blueprint->dropForeign($fkName);
                });
            }
        }

        // 2. Converter user_id → employee_id (só MySQL: em SQLite não há dados a converter)
        foreach (self::TABLES as $table => $fkName) {
            if (Schema::getConnection()->getDriverName() !== 'mysql') {
                break;
            }

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
        }

        // 3. Limpar responsible_id inválido (user sem employee correspondente)
        foreach (array_keys(self::TABLES) as $table) {
            if (Schema::getConnection()->getDriverName() !== 'mysql') {
                break;
            }

            DB::statement("
                UPDATE {$table} t
                SET t.responsible_id = NULL
                WHERE t.responsible_id IS NOT NULL
                  AND NOT EXISTS (SELECT 1 FROM employees e WHERE e.id = t.responsible_id)
            ");
        }

        // 4. Re-criar as FKs apontando para employees
        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('responsible_id')->references('id')->on('employees')->nullOnDelete();
        });

        Schema::table('areas', function (Blueprint $table) {
            $table->foreign('responsible_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // 1. Remover as FKs atuais (apontam para employees)
        foreach (self::TABLES as $table => $fkName) {
            if ($this->foreignKeyExists($table, $fkName)) {
                Schema::table($table, function (Blueprint $blueprint) use ($fkName) {
                    $blueprint->dropForeign($fkName);
                });
            }
        }

        // 2. Reverter: employee_id → user_id (quando tiver user associado)
        foreach (array_keys(self::TABLES) as $table) {
            if (Schema::getConnection()->getDriverName() !== 'mysql') {
                break;
            }

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
        }

        // 3. Re-criar as FKs apontando para users
        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('responsible_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('areas', function (Blueprint $table) {
            $table->foreign('responsible_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
