<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_requests', function (Blueprint $table) {
            $table->foreignId('extends_request_id')
                ->nullable()
                ->after('attendance_request_type_id')
                ->constrained('attendance_requests')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendance_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('extends_request_id');
        });
    }
};
