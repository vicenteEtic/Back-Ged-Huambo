<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->boolean('allows_extension')->default(false);
            $table->integer('extension_days')->nullable();
            $table->integer('max_extensions')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn(['allows_extension', 'extension_days', 'max_extensions']);
        });
    }
};
