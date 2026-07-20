<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasColumn = count(DB::select("SHOW COLUMNS FROM `leave_plans` LIKE 'leave_type_id'")) > 0;
        $oldIdx = count(DB::select("SHOW INDEX FROM `leave_plans` WHERE Key_name = 'leave_plans_employee_id_year_unique'")) > 0;
        $newIdx = count(DB::select("SHOW INDEX FROM `leave_plans` WHERE Key_name = 'leave_plans_employee_id_year_leave_type_id_unique'")) > 0;
        $fkType = count(DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leave_plans' AND COLUMN_NAME = 'leave_type_id' AND REFERENCED_TABLE_NAME = 'leave_types'"
        )) > 0;

        if (!$hasColumn) {
            Schema::table('leave_plans', function (Blueprint $table) {
                $table->foreignId('leave_type_id')->nullable()->after('employee_id')->constrained('leave_types')->nullOnDelete();
            });
        } elseif (!$fkType) {
            Schema::table('leave_plans', function (Blueprint $table) {
                $table->foreign('leave_type_id')->references('id')->on('leave_types')->nullOnDelete();
            });
        }

        Schema::withoutForeignKeyConstraints(function () use ($oldIdx, $newIdx) {
            if ($oldIdx) {
                DB::statement("ALTER TABLE `leave_plans` DROP INDEX `leave_plans_employee_id_year_unique`");
            }
            if (!$newIdx) {
                DB::statement("ALTER TABLE `leave_plans` ADD UNIQUE INDEX `leave_plans_employee_id_year_leave_type_id_unique` (`employee_id`, `year`, `leave_type_id`)");
            }
        });
    }

    public function down(): void
    {
        Schema::withoutForeignKeyConstraints(function () {
            DB::statement("ALTER TABLE `leave_plans` DROP INDEX `leave_plans_employee_id_year_leave_type_id_unique`");
            DB::statement("ALTER TABLE `leave_plans` ADD UNIQUE INDEX `leave_plans_employee_id_year_unique` (`employee_id`, `year`)");
        });

        Schema::table('leave_plans', function (Blueprint $table) {
            $table->dropForeignId('leave_type_id');
            $table->dropColumn('leave_type_id');
        });
    }
};
