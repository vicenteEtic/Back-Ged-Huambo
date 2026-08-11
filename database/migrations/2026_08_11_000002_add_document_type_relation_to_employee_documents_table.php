<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->foreignId('document_type_id')->nullable()->after('employee_id')
                ->constrained('document_types')->nullOnDelete();
            $table->date('issue_date')->nullable()->after('expiry_date');
            $table->string('place_of_issue')->nullable()->after('issue_date');
        });
    }

    public function down(): void
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('document_type_id');
            $table->dropColumn(['issue_date', 'place_of_issue']);
        });
    }
};
