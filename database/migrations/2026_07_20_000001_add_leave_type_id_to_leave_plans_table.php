<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $pdo = DB::connection()->getPdo();

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        $pdo->exec("ALTER TABLE `leave_requests` DROP FOREIGN KEY `leave_requests_leave_plan_id_foreign`");

        $hasColumn = count(DB::select("SHOW COLUMNS FROM `leave_plans` LIKE 'leave_type_id'")) > 0;
        if (!$hasColumn) {
            $pdo->exec("ALTER TABLE `leave_plans` ADD COLUMN `leave_type_id` BIGINT UNSIGNED NULL AFTER `employee_id`");
        }

        $oldIdx = count(DB::select("SHOW INDEX FROM `leave_plans` WHERE Key_name = 'leave_plans_employee_id_year_unique'"));
        if (!empty($oldIdx)) {
            $pdo->exec("ALTER TABLE `leave_plans` DROP INDEX `leave_plans_employee_id_year_unique`");
        }

        $newIdx = count(DB::select("SHOW INDEX FROM `leave_plans` WHERE Key_name = 'leave_plans_employee_id_year_leave_type_id_unique'"));
        if (empty($newIdx)) {
            $pdo->exec("ALTER TABLE `leave_plans` ADD UNIQUE INDEX `leave_plans_employee_id_year_leave_type_id_unique` (`employee_id`, `year`, `leave_type_id`)");
        }

        $fkType = count(DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leave_plans' AND COLUMN_NAME = 'leave_type_id' AND REFERENCED_TABLE_NAME = 'leave_types'"
        ));
        if (empty($fkType)) {
            $pdo->exec("ALTER TABLE `leave_plans` ADD CONSTRAINT `leave_plans_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types`(`id`) ON DELETE SET NULL");
        }

        $pdo->exec("ALTER TABLE `leave_requests` ADD CONSTRAINT `leave_requests_leave_plan_id_foreign` FOREIGN KEY (`leave_plan_id`) REFERENCES `leave_plans`(`id`) ON DELETE SET NULL");

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }

    public function down(): void
    {
        $pdo = DB::connection()->getPdo();

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        $pdo->exec("ALTER TABLE `leave_requests` DROP FOREIGN KEY `leave_requests_leave_plan_id_foreign`");

        $fkType = count(DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leave_plans' AND COLUMN_NAME = 'leave_type_id' AND REFERENCED_TABLE_NAME = 'leave_types'"
        ));
        if (!empty($fkType)) {
            $pdo->exec("ALTER TABLE `leave_plans` DROP FOREIGN KEY `leave_plans_leave_type_id_foreign`");
        }

        $newIdx = count(DB::select("SHOW INDEX FROM `leave_plans` WHERE Key_name = 'leave_plans_employee_id_year_leave_type_id_unique'"));
        if (!empty($newIdx)) {
            $pdo->exec("ALTER TABLE `leave_plans` DROP INDEX `leave_plans_employee_id_year_leave_type_id_unique`");
        }

        $hasColumn = count(DB::select("SHOW COLUMNS FROM `leave_plans` LIKE 'leave_type_id'"));
        if (!empty($hasColumn)) {
            $pdo->exec("ALTER TABLE `leave_plans` DROP COLUMN `leave_type_id`");
        }

        $pdo->exec("ALTER TABLE `leave_plans` ADD UNIQUE INDEX `leave_plans_employee_id_year_unique` (`employee_id`, `year`)");

        $pdo->exec("ALTER TABLE `leave_requests` ADD CONSTRAINT `leave_requests_leave_plan_id_foreign` FOREIGN KEY (`leave_plan_id`) REFERENCES `leave_plans`(`id`) ON DELETE SET NULL");

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
};
