<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('leave_types', 'service_years_based')) {
            Schema::table('leave_types', function (Blueprint $table) {
                $table->boolean('service_years_based')->default(false)->after('default_days');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('leave_types', 'service_years_based')) {
            Schema::table('leave_types', function (Blueprint $table) {
                $table->dropColumn('service_years_based');
            });
        }
    }
};
