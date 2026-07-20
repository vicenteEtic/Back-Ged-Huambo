<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $hasColumn = count(DB::select("SHOW COLUMNS FROM `leave_plans` LIKE 'leave_type_id'")) > 0;
        $oldIndex = count(DB::select("SHOW COLUMNS FROM `leave_plans` WHERE Field = 'employee_id'")) > 0;

        $sqls = ["SET FOREIGN_KEY_CHECKS = 0"];

        if (!$hasColumn) {
            $sqls[] = "ALTER TABLE `leave_plans` ADD COLUMN `leave_type_id` BIGINT UNSIGNED NULL AFTER `employee_id`";
        }

        $oldIdx = count(DB::select("SHOW INDEX FROM `leave_plans` WHERE Key_name = 'leave_plans_employee_id_year_unique'")) > 0;
        $newIdx = count(DB::select("SHOW INDEX FROM `leave_plans` WHERE Key_name = 'leave_plans_employee_id_year_leave_type_id_unique'")) > 0;

        if ($oldIdx) {
            $sqls[] = "ALTER TABLE `leave_plans` DROP INDEX `leave_plans_employee_id_year_unique`";
        }
        if (!$newIdx) {
            $sqls[] = "ALTER TABLE `leave_plans` ADD UNIQUE INDEX `leave_plans_employee_id_year_leave_type_id_unique` (`employee_id`, `year`, `leave_type_id`)";
        }

        $fkExists = count(DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leave_plans' AND COLUMN_NAME = 'leave_type_id' AND REFERENCED_TABLE_NAME = 'leave_types'"
        )) > 0;

        if (!$fkExists) {
            $sqls[] = "ALTER TABLE `leave_plans` ADD CONSTRAINT `leave_plans_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types`(`id`) ON DELETE SET NULL";
        }

        $sqls[] = "SET FOREIGN_KEY_CHECKS = 1";

        DB::unprepared(implode('; ', $sqls));
    }

    public function down(): void
    {
        $sqls = [
            "SET FOREIGN_KEY_CHECKS = 0",
            "ALTER TABLE `leave_plans` DROP FOREIGN KEY `leave_plans_leave_type_id_foreign`",
            "ALTER TABLE `leave_plans` DROP INDEX `leave_plans_employee_id_year_leave_type_id_unique`",
            "ALTER TABLE `leave_plans` DROP COLUMN `leave_type_id`",
            "ALTER TABLE `leave_plans` ADD UNIQUE INDEX `leave_plans_employee_id_year_unique` (`employee_id`, `year`)",
            "SET FOREIGN_KEY_CHECKS = 1",
        ];

        DB::unprepared(implode('; ', $sqls));
    }
};
