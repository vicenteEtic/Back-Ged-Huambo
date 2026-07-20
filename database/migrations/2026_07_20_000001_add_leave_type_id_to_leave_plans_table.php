<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        // Adicionar coluna
        $hasColumn = DB::select("SHOW COLUMNS FROM `leave_plans` LIKE 'leave_type_id'");
        if (empty($hasColumn)) {
            DB::statement("ALTER TABLE `leave_plans` ADD COLUMN `leave_type_id` BIGINT UNSIGNED NULL AFTER `employee_id`");
        }

        // Trocar o índice único: de (employee_id, year) para (employee_id, year, leave_type_id)
        DB::statement("ALTER TABLE `leave_plans` DROP INDEX `leave_plans_employee_id_year_unique`");
        DB::statement("ALTER TABLE `leave_plans` ADD UNIQUE INDEX `leave_plans_employee_id_year_leave_type_id_unique` (`employee_id`, `year`, `leave_type_id`)");

        // Adicionar FK para leave_type_id se não existir
        $fkExists = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'leave_plans' AND COLUMN_NAME = 'leave_type_id' AND REFERENCED_TABLE_NAME = 'leave_types'");
        if (empty($fkExists)) {
            DB::statement("ALTER TABLE `leave_plans` ADD CONSTRAINT `leave_plans_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types`(`id`) ON DELETE SET NULL");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        DB::statement("ALTER TABLE `leave_plans` DROP INDEX `leave_plans_employee_id_year_leave_type_id_unique`");

        $fkExists = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'leave_plans' AND COLUMN_NAME = 'leave_type_id' AND REFERENCED_TABLE_NAME = 'leave_types'");
        foreach ($fkExists as $fk) {
            DB::statement("ALTER TABLE `leave_plans` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        DB::statement("ALTER TABLE `leave_plans` DROP COLUMN `leave_type_id`");
        DB::statement("ALTER TABLE `leave_plans` ADD UNIQUE INDEX `leave_plans_employee_id_year_unique` (`employee_id`, `year`)");

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }
};
