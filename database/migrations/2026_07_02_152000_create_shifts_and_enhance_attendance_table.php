<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('grace_minutes')->default(0);
            $table->decimal('duration_hours', 4, 2)->default(8);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('shift_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->date('effective_date');
            $table->date('end_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'effective_date']);
        });

        Schema::table('attendance', function (Blueprint $table) {
            $table->string('status')->default('present')->after('date');
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete()->after('employee_id');
            $table->time('expected_check_in')->nullable()->after('shift_id');
            $table->time('expected_check_out')->nullable()->after('expected_check_in');
            $table->integer('late_minutes')->default(0)->after('hours_worked');
            $table->integer('overtime_minutes')->default(0)->after('late_minutes');
            $table->string('absence_type')->nullable()->after('overtime_minutes');
            $table->string('absence_reason')->nullable()->after('absence_type');
            $table->boolean('is_justified')->default(false)->after('absence_reason');
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropColumn(['status', 'shift_id', 'expected_check_in', 'expected_check_out', 'late_minutes', 'overtime_minutes', 'absence_type', 'absence_reason', 'is_justified']);
        });
        Schema::dropIfExists('shift_assignments');
        Schema::dropIfExists('shifts');
    }
};
