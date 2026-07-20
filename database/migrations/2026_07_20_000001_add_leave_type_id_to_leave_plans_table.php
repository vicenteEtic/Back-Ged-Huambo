<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $pdo = DB::connection()->getPdo();

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        // Drop FK de leave_requests que referencia leave_plans
        $fks = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leave_requests' AND COLUMN_NAME = 'leave_plan_id' AND REFERENCED_TABLE_NAME = 'leave_plans'"
        );
        foreach ($fks as $fk) {
            $pdo->exec("ALTER TABLE `leave_requests` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        // Adicionar coluna se não existir
        $hasColumn = DB::select("SHOW COLUMNS FROM `leave_plans` LIKE 'leave_type_id'");
        if (empty($hasColumn)) {
            $pdo->exec("ALTER TABLE `leave_plans` ADD COLUMN `leave_type_id` BIGINT UNSIGNED NULL AFTER `employee_id`");
        }

        // Trocar índice único
        $oldIdx = DB::select("SHOW INDEX FROM `leave_plans` WHERE Key_name = 'leave_plans_employee_id_year_unique'");
        if (!empty($oldIdx)) {
            $pdo->exec("ALTER TABLE `leave_plans` DROP INDEX `leave_plans_employee_id_year_unique`");
        }

        $newIdx = DB::select("SHOW INDEX FROM `leave_plans` WHERE Key_name = 'leave_plans_employee_id_year_leave_type_id_unique'");
        if (empty($newIdx)) {
            $pdo->exec("ALTER TABLE `leave_plans` ADD UNIQUE INDEX `leave_plans_employee_id_year_leave_type_id_unique` (`employee_id`, `year`, `leave_type_id`)");
        }

        // Adicionar FK para leave_types
        $fkType = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leave_plans' AND COLUMN_NAME = 'leave_type_id' AND REFERENCED_TABLE_NAME = 'leave_types'"
        );
        if (empty($fkType)) {
            $pdo->exec("ALTER TABLE `leave_plans` ADD CONSTRAINT `leave_plans_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types`(`id`) ON DELETE SET NULL");
        }

        // Recriar FK em leave_requests
        $pdo->exec("ALTER TABLE `leave_requests` ADD CONSTRAINT `leave_requests_leave_plan_id_foreign` FOREIGN KEY (`leave_plan_id`) REFERENCES `leave_plans`(`id`) ON DELETE SET NULL");

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }

    public function down(): void
    {
        $pdo = DB::connection()->getPdo();

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        $fks = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leave_requests' AND COLUMN_NAME = 'leave_plan_id' AND REFERENCED_TABLE_NAME = 'leave_plans'"
        );
        foreach ($fks as $fk) {
            $pdo->exec("ALTER TABLE `leave_requests` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        $fkType = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leave_plans' AND COLUMN_NAME = 'leave_type_id' AND REFERENCED_TABLE_NAME = 'leave_types'"
        );
        foreach ($fkType as $fk) {
            $pdo->exec("ALTER TABLE `leave_plans` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        $pdo->exec("ALTER TABLE `leave_plans` DROP INDEX `leave_plans_employee_id_year_leave_type_id_unique`");
        $pdo->exec("ALTER TABLE `leave_plans` DROP COLUMN `leave_type_id`");
        $pdo->exec("ALTER TABLE `leave_plans` ADD UNIQUE INDEX `leave_plans_employee_id_year_unique` (`employee_id`, `year`)");

        $pdo->exec("ALTER TABLE `leave_requests` ADD CONSTRAINT `leave_requests_leave_plan_id_foreign` FOREIGN KEY (`leave_plan_id`) REFERENCES `leave_plans`(`id`) ON DELETE SET NULL");

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
};
