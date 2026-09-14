<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->date('end_date')->nullable()->change();
            $table->integer('total_days')->nullable()->change();
            $table->date('return_date')->nullable()->change();
            $table->foreignId('extends_request_id')->nullable()->constrained('leave_requests')->nullOnDelete();
            $table->integer('extension_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropForeign(['extends_request_id']);
            $table->dropColumn(['extends_request_id', 'extension_count']);
            $table->date('end_date')->nullable(false)->change();
            $table->integer('total_days')->nullable(false)->change();
            $table->date('return_date')->nullable(false)->change();
        });
    }
};
