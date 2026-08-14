<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function isMySql(): bool
    {
        return DB::connection()->getDriverName() === 'mysql';
    }

    private function hasForeignKey(string $table, string $column, string $referencedTable): array
    {
        return DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = '{$table}'
               AND COLUMN_NAME = '{$column}'
               AND REFERENCED_TABLE_NAME = '{$referencedTable}'"
        );
    }

    private function dropForeignKey(array $fkRows, string $table): void
    {
        foreach ($fkRows as $fk) {
            DB::connection()->getPdo()->exec("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }
    }

    public function up(): void
    {
        if (!Schema::hasColumn('leave_plans', 'leave_type_id')) {
            Schema::table('leave_plans', function (Blueprint $table) {
                $table->foreignId('leave_type_id')->nullable()->after('employee_id');
            });
        }

        if ($this->isMySql()) {
            $this->dropForeignKey($this->hasForeignKey('leave_plans', 'employee_id', 'employees'), 'leave_plans');
        }

        if (Schema::hasIndex('leave_plans', ['employee_id', 'year'])) {
            Schema::table('leave_plans', function (Blueprint $table) {
                $table->dropUnique(['employee_id', 'year']);
            });
        }

        if (!Schema::hasIndex('leave_plans', ['employee_id', 'year', 'leave_type_id'])) {
            Schema::table('leave_plans', function (Blueprint $table) {
                $table->unique(['employee_id', 'year', 'leave_type_id'], 'leave_plans_employee_id_year_leave_type_id_unique');
            });
        }

        if ($this->isMySql()) {
            $pdo = DB::connection()->getPdo();
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

            if (empty($this->hasForeignKey('leave_plans', 'employee_id', 'employees'))) {
                $pdo->exec('ALTER TABLE `leave_plans` ADD CONSTRAINT `leave_plans_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE');
            }

            $fkLR = $this->hasForeignKey('leave_requests', 'leave_plan_id', 'leave_plans');
            if (!empty($fkLR)) {
                $pdo->exec('ALTER TABLE `leave_requests` DROP FOREIGN KEY `leave_requests_leave_plan_id_foreign`');
            }

            $fkType = $this->hasForeignKey('leave_plans', 'leave_type_id', 'leave_types');
            if (empty($fkType)) {
                $pdo->exec('ALTER TABLE `leave_plans` ADD CONSTRAINT `leave_plans_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types`(`id`) ON DELETE SET NULL');
            }

            $pdo->exec('ALTER TABLE `leave_requests` ADD CONSTRAINT `leave_requests_leave_plan_id_foreign` FOREIGN KEY (`leave_plan_id`) REFERENCES `leave_plans`(`id`) ON DELETE SET NULL');

            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    public function down(): void
    {
        if ($this->isMySql()) {
            $this->dropForeignKey($this->hasForeignKey('leave_requests', 'leave_plan_id', 'leave_plans'), 'leave_requests');
            $this->dropForeignKey($this->hasForeignKey('leave_plans', 'leave_type_id', 'leave_types'), 'leave_plans');
            $this->dropForeignKey($this->hasForeignKey('leave_plans', 'employee_id', 'employees'), 'leave_plans');
        }

        if (Schema::hasIndex('leave_plans', ['employee_id', 'year', 'leave_type_id'])) {
            Schema::table('leave_plans', function (Blueprint $table) {
                $table->dropUnique('leave_plans_employee_id_year_leave_type_id_unique');
            });
        }

        if (Schema::hasColumn('leave_plans', 'leave_type_id')) {
            Schema::table('leave_plans', function (Blueprint $table) {
                $table->dropColumn('leave_type_id');
            });
        }

        if (!Schema::hasIndex('leave_plans', ['employee_id', 'year'])) {
            Schema::table('leave_plans', function (Blueprint $table) {
                $table->unique(['employee_id', 'year']);
            });
        }

        if ($this->isMySql()) {
            if (empty($this->hasForeignKey('leave_plans', 'employee_id', 'employees'))) {
                DB::connection()->getPdo()->exec('ALTER TABLE `leave_plans` ADD CONSTRAINT `leave_plans_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE');
            }
        }
    }
};
