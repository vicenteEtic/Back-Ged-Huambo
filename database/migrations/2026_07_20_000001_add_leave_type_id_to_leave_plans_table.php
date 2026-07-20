<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Se a coluna já existe, o estado está parcial — corrigir
        $hasColumn = count(DB::select("SHOW COLUMNS FROM `leave_plans` LIKE 'leave_type_id'")) > 0;

        if (!$hasColumn) {
            // Estado limpo — executar tudo de uma vez
            DB::unprepared("
                SET FOREIGN_KEY_CHECKS = 0;

                ALTER TABLE `leave_plans` ADD COLUMN `leave_type_id` BIGINT UNSIGNED NULL AFTER `employee_id`;
                ALTER TABLE `leave_plans` DROP INDEX `leave_plans_employee_id_year_unique`;
                ALTER TABLE `leave_plans` ADD UNIQUE INDEX `leave_plans_employee_id_year_leave_type_id_unique` (`employee_id`, `year`, `leave_type_id`);
                ALTER TABLE `leave_plans` ADD CONSTRAINT `leave_plans_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types`(`id`) ON DELETE SET NULL;

                SET FOREIGN_KEY_CHECKS = 1;
            ");
        } else {
            // Estado parcial — corrigir passo a passo
            DB::unprepared("SET FOREIGN_KEY_CHECKS = 0;");

            // Garantir que a FK para leave_types existe
            $fkExists = count(DB::select(
                "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leave_plans' AND COLUMN_NAME = 'leave_type_id' AND REFERENCED_TABLE_NAME = 'leave_types'"
            )) > 0;

            if (!$fkExists) {
                DB::statement("ALTER TABLE `leave_plans` ADD CONSTRAINT `leave_plans_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types`(`id`) ON DELETE SET NULL");
            }

            // Trocar o índice único se necessário
            $oldIndex = count(DB::select("SHOW INDEX FROM `leave_plans` WHERE Key_name = 'leave_plans_employee_id_year_unique'")) > 0;
            $newIndex = count(DB::select("SHOW INDEX FROM `leave_plans` WHERE Key_name = 'leave_plans_employee_id_year_leave_type_id_unique'")) > 0;

            if ($oldIndex && !$newIndex) {
                DB::statement("ALTER TABLE `leave_plans` DROP INDEX `leave_plans_employee_id_year_unique`");
                DB::statement("ALTER TABLE `leave_plans` ADD UNIQUE INDEX `leave_plans_employee_id_year_leave_type_id_unique` (`employee_id`, `year`, `leave_type_id`)");
            } elseif ($oldIndex && $newIndex) {
                DB::statement("ALTER TABLE `leave_plans` DROP INDEX `leave_plans_employee_id_year_unique`");
            }
            // Se só $newIndex existe, está tudo bem

            DB::unprepared("SET FOREIGN_KEY_CHECKS = 1;");
        }
    }

    public function down(): void
    {
        DB::unprepared("
            SET FOREIGN_KEY_CHECKS = 0;

            ALTER TABLE `leave_plans` DROP FOREIGN KEY `leave_plans_leave_type_id_foreign`;
            ALTER TABLE `leave_plans` DROP INDEX `leave_plans_employee_id_year_leave_type_id_unique`;
            ALTER TABLE `leave_plans` DROP COLUMN `leave_type_id`;
            ALTER TABLE `leave_plans` ADD UNIQUE INDEX `leave_plans_employee_id_year_unique` (`employee_id`, `year`);

            SET FOREIGN_KEY_CHECKS = 1;
        ");
    }
};
