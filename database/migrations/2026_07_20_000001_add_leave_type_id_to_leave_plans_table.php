<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Remover a FK que referencia leave_plans (via raw SQL)
        $constraints = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'leave_requests' AND COLUMN_NAME = 'leave_plan_id' AND REFERENCED_TABLE_NAME = 'leave_plans'");
        foreach ($constraints as $c) {
            DB::statement("ALTER TABLE `leave_requests` DROP FOREIGN KEY `{$c->CONSTRAINT_NAME}`");
        }

        // 2. Remover o índice único antigo (via raw SQL)
        $indexes = DB::select("SHOW INDEX FROM `leave_plans` WHERE Key_name = 'leave_plans_employee_id_year_unique'");
        if (!empty($indexes)) {
            DB::statement("ALTER TABLE `leave_plans` DROP INDEX `leave_plans_employee_id_year_unique`");
        }

        // 3. Adicionar coluna leave_type_id
        $hasColumn = DB::select("SHOW COLUMNS FROM `leave_plans` LIKE 'leave_type_id'");
        if (empty($hasColumn)) {
            DB::statement("ALTER TABLE `leave_plans` ADD COLUMN `leave_type_id` BIGINT UNSIGNED NULL AFTER `employee_id`");
            DB::statement("ALTER TABLE `leave_plans` ADD CONSTRAINT `leave_plans_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types`(`id`) ON DELETE SET NULL");
        }

        // 4. Criar novo índice único composto
        DB::statement("ALTER TABLE `leave_plans` ADD UNIQUE INDEX `leave_plans_employee_id_year_leave_type_id_unique` (`employee_id`, `year`, `leave_type_id`)");

        // 5. Recriar FK em leave_requests
        DB::statement("ALTER TABLE `leave_requests` ADD CONSTRAINT `leave_requests_leave_plan_id_foreign` FOREIGN KEY (`leave_plan_id`) REFERENCES `leave_plans`(`id`) ON DELETE SET NULL");
    }

    public function down(): void
    {
        $constraints = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'leave_requests' AND COLUMN_NAME = 'leave_plan_id' AND REFERENCED_TABLE_NAME = 'leave_plans'");
        foreach ($constraints as $c) {
            DB::statement("ALTER TABLE `leave_requests` DROP FOREIGN KEY `{$c->CONSTRAINT_NAME}`");
        }

        DB::statement("ALTER TABLE `leave_plans` DROP INDEX `leave_plans_employee_id_year_leave_type_id_unique`");

        $fkExists = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'leave_plans' AND COLUMN_NAME = 'leave_type_id' AND REFERENCED_TABLE_NAME = 'leave_types'");
        foreach ($fkExists as $fk) {
            DB::statement("ALTER TABLE `leave_plans` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        DB::statement("ALTER TABLE `leave_plans` DROP COLUMN `leave_type_id`");
        DB::statement("ALTER TABLE `leave_plans` ADD UNIQUE INDEX `leave_plans_employee_id_year_unique` (`employee_id`, `year`)");

        DB::statement("ALTER TABLE `leave_requests` ADD CONSTRAINT `leave_requests_leave_plan_id_foreign` FOREIGN KEY (`leave_plan_id`) REFERENCES `leave_plans`(`id`) ON DELETE SET NULL");
    }
};
