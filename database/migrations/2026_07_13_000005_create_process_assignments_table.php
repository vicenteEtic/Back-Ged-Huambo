<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_id')->constrained('processes')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments');
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('assigned_by')->constrained('users');
            $table->string('visibility')->default('private'); // private, public
            $table->string('status')->default('pending'); // pending, processing, pending_validation, validated, correction_requested, completed
            $table->string('priority')->default('normal');
            $table->date('deadline')->nullable();
            $table->text('notes')->nullable();
            $table->text('result_notes')->nullable();
            $table->string('result_file_path')->nullable();
            $table->string('result_file_type')->nullable();
            $table->unsignedBigInteger('result_file_size')->nullable();
            $table->string('result_mime_type')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_assignments');
    }
};
