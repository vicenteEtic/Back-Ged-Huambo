<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropForeign('leave_requests_leave_plan_id_foreign');
        });

        Schema::table('leave_plans', function (Blueprint $table) {
            $table->dropUnique('leave_plans_employee_id_year_unique');
        });

        Schema::table('leave_plans', function (Blueprint $table) {
            $table->foreignId('leave_type_id')->nullable()->after('employee_id')->constrained('leave_types')->nullOnDelete();
        });

        Schema::table('leave_plans', function (Blueprint $table) {
            $table->unique(['employee_id', 'year', 'leave_type_id']);
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreign('leave_plan_id')->references('id')->on('leave_plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropForeign('leave_requests_leave_plan_id_foreign');
        });

        Schema::table('leave_plans', function (Blueprint $table) {
            $table->dropUnique('leave_plans_employee_id_year_leave_type_id_unique');
            $table->dropForeignId('leave_type_id');
            $table->dropColumn('leave_type_id');
        });

        Schema::table('leave_plans', function (Blueprint $table) {
            $table->unique(['employee_id', 'year']);
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreign('leave_plan_id')->references('id')->on('leave_plans')->nullOnDelete();
        });
    }
};
