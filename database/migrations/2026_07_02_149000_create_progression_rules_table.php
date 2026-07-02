<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progression_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type')->default('progression');
            $table->text('description')->nullable();
            $table->integer('min_months_in_category')->default(0);
            $table->decimal('min_performance_score', 5, 2)->nullable();
            $table->boolean('requires_training')->default(false);
            $table->boolean('requires_evaluation')->default(true);
            $table->string('from_category')->nullable();
            $table->string('to_category')->nullable();
            $table->integer('from_level')->nullable();
            $table->integer('to_level')->nullable();
            $table->decimal('salary_increase_percent', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progression_rules');
    }
};
