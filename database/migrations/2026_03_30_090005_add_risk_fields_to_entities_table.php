<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('alert', function (Blueprint $table) {
            if (!Schema::hasColumn('alert', 'risk_assessment_id')) {
                $table->unsignedBigInteger('risk_assessment_id')->nullable()->after('id');
            }
        
            if (!Schema::hasColumn('alert', 'alert_priority')) {
                $table->boolean('alert_priority')->default(false)->after('risk_assessment_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('alert', function (Blueprint $table) {
            $table->dropForeign(['risk_assessment_id']);
            $table->dropColumn(['risk_assessment_id', 'alert_priority']);
        });
    }
};
