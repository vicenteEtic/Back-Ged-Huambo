<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_assignment_technicians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_assignment_id')->constrained('process_assignments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('assigned_by')->constrained('users');
            $table->string('status')->default('pending'); // pending, processing, submitted, validated
            $table->text('notes')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_assignment_technicians');
    }
};
