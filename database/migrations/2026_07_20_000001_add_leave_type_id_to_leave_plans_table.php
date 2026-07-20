<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_plans', function (Blueprint $table) {
            $table->foreignId('leave_type_id')->nullable()->after('employee_id')->constrained('leave_types')->nullOnDelete();
        });

        DB::statement('UPDATE leave_plans SET leave_type_id = NULL');

        Schema::table('leave_plans', function (Blueprint $table) {
            $table->dropUnique(['employee_id', 'year']);
            $table->unique(['employee_id', 'year', 'leave_type_id']);
        });
    }

    public function down(): void
    {
        Schema::table('leave_plans', function (Blueprint $table) {
            $table->dropUnique(['employee_id', 'year', 'leave_type_id']);
            $table->dropForeignId('leave_type_id');
            $table->dropColumn('leave_type_id');
        });

        Schema::table('leave_plans', function (Blueprint $table) {
            $table->unique(['employee_id', 'year']);
        });
    }
};
