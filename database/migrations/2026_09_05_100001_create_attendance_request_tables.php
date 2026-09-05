<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_request_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('attendance_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('attendance_request_type_id')->constrained('attendance_request_types')->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('applies_full_day')->default(true);
            $table->text('reason')->nullable();
            $table->text('description')->nullable();
            $table->text('oversight_note')->nullable();
            $table->string('status')->default('pending');
            $table->date('benefit_start_date')->nullable();
            $table->date('benefit_until')->nullable();
            $table->boolean('benefit_active')->default(true);
            $table->string('despacho_number')->nullable();
            $table->string('despacho_path')->nullable();
            $table->string('despacho_decision')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'status']);
        });

        Schema::create('attendance_request_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_request_id')->constrained('attendance_requests')->cascadeOnDelete();
            $table->string('document_type')->nullable();
            $table->string('original_name')->nullable();
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('attendance_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_request_id')->constrained('attendance_requests')->cascadeOnDelete();
            $table->string('action');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['attendance_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_request_logs');
        Schema::dropIfExists('attendance_request_documents');
        Schema::dropIfExists('attendance_requests');
        Schema::dropIfExists('attendance_request_types');
    }
};
