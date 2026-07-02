<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progression_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('rule_id')->nullable()->constrained('progression_rules')->nullOnDelete();
            $table->string('type'); // progression, promotion
            $table->string('from_category')->nullable();
            $table->string('to_category')->nullable();
            $table->foreignId('from_position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignId('to_position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->decimal('current_salary', 12, 2)->default(0);
            $table->decimal('new_salary', 12, 2)->default(0);
            $table->decimal('increase_percent', 5, 2)->default(0);
            $table->text('justification')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('effective_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('progression_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('progression_request_id')->constrained('progression_requests')->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
            $table->integer('level')->default(1);
            $table->string('status')->default('pending');
            $table->text('comment')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['progression_request_id', 'approver_id'], 'approval_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progression_approvals');
        Schema::dropIfExists('progression_requests');
    }
};
