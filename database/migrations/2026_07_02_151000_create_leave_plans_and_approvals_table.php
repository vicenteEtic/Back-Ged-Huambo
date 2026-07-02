<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->integer('year');
            $table->decimal('total_days_entitled', 6, 1)->default(22);
            $table->decimal('days_used', 6, 1)->default(0);
            $table->decimal('days_pending', 6, 1)->default(0);
            $table->text('observations')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['employee_id', 'year']);
        });

        Schema::create('leave_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained('leave_requests')->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
            $table->integer('level')->default(1);
            $table->string('status')->default('pending');
            $table->text('comment')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['leave_request_id', 'approver_id'], 'leave_approval_unique');
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreignId('leave_plan_id')->nullable()->constrained('leave_plans')->nullOnDelete()->after('leave_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('leave_plan_id');
        });
        Schema::dropIfExists('leave_approvals');
        Schema::dropIfExists('leave_plans');
    }
};
