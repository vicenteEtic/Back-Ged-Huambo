<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_plans', function (Blueprint $table) {
            $table->unsignedTinyInteger('expected_month')->nullable()->after('year');
            $table->timestamp('upcoming_notified_at')->nullable()->after('observations');
        });
    }

    public function down(): void
    {
        Schema::table('leave_plans', function (Blueprint $table) {
            $table->dropColumn(['expected_month', 'upcoming_notified_at']);
        });
    }
};
