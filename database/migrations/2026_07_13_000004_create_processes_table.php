<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processes', function (Blueprint $table) {
            $table->id();
            $table->string('process_type'); // external, internal (string para escalabilidade)
            $table->string('sequence_number')->unique();

            // Recepção
            $table->date('reception_date');
            $table->time('reception_time');
            $table->string('reference_number')->nullable();
            $table->date('document_date')->nullable();
            $table->string('subject');
            $table->text('notes')->nullable();

            // Específicos externos
            $table->string('sender_entity')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();

            // Específicos internos
            $table->text('justification')->nullable();
            $table->string('classification')->nullable(); // pedido, reclamacao, sugestao, informacao, outro
            $table->date('deadline')->nullable();

            // Fluxo
            $table->string('status')->default('received');
            $table->foreignId('current_department_id')->constrained('departments');
            $table->foreignId('current_area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('current_holder_id')->constrained('users');
            $table->foreignId('origin_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('origin_area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('target_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('visibility')->default('public'); // public, private
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->text('rejection_reason')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();

            // Controle
            $table->foreignId('received_by')->constrained('users');
            $table->foreignId('created_by')->constrained('users');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processes');
    }
};
