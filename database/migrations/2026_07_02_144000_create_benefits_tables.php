<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benefit_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('provider')->nullable();
            $table->decimal('default_amount', 12, 2)->default(0);
            $table->string('frequency')->default('monthly');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('employee_benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('benefit_type_id')->constrained('benefit_types')->cascadeOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['employee_id', 'benefit_type_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_benefits');
        Schema::dropIfExists('benefit_types');
    }
};
