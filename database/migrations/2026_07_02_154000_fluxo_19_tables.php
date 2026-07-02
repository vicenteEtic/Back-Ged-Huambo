<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // === FLUXO 19 - Gestão de Arquivos ===
        Schema::create('archive_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('archive_categories')->nullOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('type'); // processo_individual, administrativo, relatorio, avaliacao, despacho
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('archive_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('archive_categories')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('document_number')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('issuing_authority')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('status')->default('draft'); // draft, published, archived
            $table->string('confidentiality')->default('internal'); // public, internal, confidential, restricted
            $table->json('metadata')->nullable();
            $table->date('issued_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_physical_copy')->default(false);
            $table->string('physical_location')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->json('tags')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('archive_document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_document_id')->constrained('archive_documents')->cascadeOnDelete();
            $table->integer('version_number');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('archive_document_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_document_id')->constrained('archive_documents')->cascadeOnDelete();
            $table->foreignId('shared_with_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('shared_with_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('permission')->default('view'); // view, download, edit
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('shared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_document_shares');
        Schema::dropIfExists('archive_document_versions');
        Schema::dropIfExists('archive_documents');
        Schema::dropIfExists('archive_categories');
    }
};
