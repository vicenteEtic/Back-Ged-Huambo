<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_request_types', function (Blueprint $table) {
            $table->json('required_documents')->nullable()->after('description');
            $table->unsignedInteger('max_days')->nullable()->after('required_documents');
            $table->string('legal_ref', 255)->nullable()->after('max_days');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_request_types', function (Blueprint $table) {
            $table->dropColumn(['legal_ref', 'max_days', 'required_documents']);
        });
    }
};
