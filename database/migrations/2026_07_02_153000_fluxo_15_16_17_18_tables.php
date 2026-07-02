<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // === FLUXO 15 - Títulos de Vencimento ===
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->string('payslip_number')->unique();
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->decimal('transport_allowance', 12, 2)->default(0);
            $table->decimal('meal_allowance', 12, 2)->default(0);
            $table->decimal('overtime', 12, 2)->default(0);
            $table->decimal('other_earnings', 12, 2)->default(0);
            $table->decimal('gross_pay', 12, 2)->default(0);
            $table->decimal('inss_deduction', 12, 2)->default(0);
            $table->decimal('irt_deduction', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_pay', 12, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->string('status')->default('generated');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['employee_id', 'payroll_period_id']);
        });

        // === FLUXO 16 - Benefícios Sociais ===
        Schema::table('benefit_types', function (Blueprint $table) {
            $table->string('category')->nullable()->after('code');
        });

        Schema::create('benefit_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('benefit_type_id')->constrained('benefit_types')->cascadeOnDelete();
            $table->decimal('amount_requested', 12, 2)->default(0);
            $table->decimal('amount_approved', 12, 2)->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('pending');
            $table->date('requested_date');
            $table->date('approved_date')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('medical_assistance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('assistance_type');
            $table->string('provider')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('assistance_date');
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // === FLUXO 17 - Aposentação e Reforma ===
        Schema::create('retirement_eligibility', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->integer('retirement_age')->default(60);
            $table->decimal('contribution_years', 4, 1)->default(0);
            $table->decimal('minimum_contribution_years', 4, 1)->default(15);
            $table->boolean('age_eligible')->default(false);
            $table->boolean('contribution_eligible')->default(false);
            $table->date('expected_retirement_date')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('retirement_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('request_date');
            $table->date('effective_date')->nullable();
            $table->string('status')->default('draft');
            $table->decimal('final_salary', 12, 2)->default(0);
            $table->decimal('pension_amount', 12, 2)->default(0);
            $table->string('pension_type')->nullable();
            $table->text('documents')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('approved_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('post_retirement_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('retirement_process_id')->constrained('retirement_processes')->cascadeOnDelete();
            $table->date('record_date');
            $table->string('type');
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_retirement_history');
        Schema::dropIfExists('retirement_processes');
        Schema::dropIfExists('retirement_eligibility');
        Schema::dropIfExists('medical_assistance');
        Schema::dropIfExists('benefit_claims');
        Schema::table('benefit_types', function (Blueprint $table) {
            $table->dropColumn('category');
        });
        Schema::dropIfExists('payslips');
    }
};
