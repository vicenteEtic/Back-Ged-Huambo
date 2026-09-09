<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_request_types', function (Blueprint $table) {
            $table->boolean('allows_extension')->default(false)->after('sort_order');
            $table->unsignedInteger('extension_days')->nullable()->after('allows_extension');
            $table->unsignedInteger('max_extensions')->nullable()->after('extension_days');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_request_types', function (Blueprint $table) {
            $table->dropColumn(['allows_extension', 'extension_days', 'max_extensions']);
        });
    }
};
