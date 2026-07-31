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

    public function up(): void
    {
        if (!Schema::hasColumn('leave_plans', 'leave_type_id')) {
            Schema::table('leave_plans', function (Blueprint $table) {
                $table->foreignId('leave_type_id')->nullable()->after('employee_id');
            });
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

            $fkLR = count(DB::select(
                "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leave_requests' AND COLUMN_NAME = 'leave_plan_id' AND REFERENCED_TABLE_NAME = 'leave_plans'"
            ));
            if (!empty($fkLR)) {
                $pdo->exec('ALTER TABLE `leave_requests` DROP FOREIGN KEY `leave_requests_leave_plan_id_foreign`');
            }

            $fkType = count(DB::select(
                "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leave_plans' AND COLUMN_NAME = 'leave_type_id' AND REFERENCED_TABLE_NAME = 'leave_types'"
            ));
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
            $pdo = DB::connection()->getPdo();
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

            $fkLR = count(DB::select(
                "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leave_requests' AND COLUMN_NAME = 'leave_plan_id' AND REFERENCED_TABLE_NAME = 'leave_plans'"
            ));
            if (!empty($fkLR)) {
                $pdo->exec('ALTER TABLE `leave_requests` DROP FOREIGN KEY `leave_requests_leave_plan_id_foreign`');
            }

            $fkType = count(DB::select(
                "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leave_plans' AND COLUMN_NAME = 'leave_type_id' AND REFERENCED_TABLE_NAME = 'leave_types'"
            ));
            if (!empty($fkType)) {
                $pdo->exec('ALTER TABLE `leave_plans` DROP FOREIGN KEY `leave_plans_leave_type_id_foreign`');
            }

            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
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
    }
};
