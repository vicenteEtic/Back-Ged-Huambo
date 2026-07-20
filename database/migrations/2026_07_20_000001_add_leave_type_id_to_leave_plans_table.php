<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sql = "
            SET FOREIGN_KEY_CHECKS = 0;

            ALTER TABLE `leave_plans` ADD COLUMN `leave_type_id` BIGINT UNSIGNED NULL AFTER `employee_id`;

            ALTER TABLE `leave_plans` DROP INDEX `leave_plans_employee_id_year_unique`;
            ALTER TABLE `leave_plans` ADD UNIQUE INDEX `leave_plans_employee_id_year_leave_type_id_unique` (`employee_id`, `year`, `leave_type_id`);

            ALTER TABLE `leave_plans` ADD CONSTRAINT `leave_plans_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types`(`id`) ON DELETE SET NULL;

            SET FOREIGN_KEY_CHECKS = 1;
        ";

        DB::unprepared($sql);
    }

    public function down(): void
    {
        $sql = "
            SET FOREIGN_KEY_CHECKS = 0;

            ALTER TABLE `leave_plans` DROP FOREIGN KEY `leave_plans_leave_type_id_foreign`;
            ALTER TABLE `leave_plans` DROP INDEX `leave_plans_employee_id_year_leave_type_id_unique`;
            ALTER TABLE `leave_plans` DROP COLUMN `leave_type_id`;
            ALTER TABLE `leave_plans` ADD UNIQUE INDEX `leave_plans_employee_id_year_unique` (`employee_id`, `year`);

            SET FOREIGN_KEY_CHECKS = 1;
        ";

        DB::unprepared($sql);
    }
};
