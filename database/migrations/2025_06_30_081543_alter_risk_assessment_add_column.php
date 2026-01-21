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
        Schema::table('risk_assessment', function (Blueprint $table) {
            $table->tinyInteger('type_assessment')
      ->default(1)
      ->after('user_id')
      ->comment('1: Normal, 2: Import, 3: Automatic Update Score, 4: Automatic');

            $table->tinyInteger('status')->default(1)->after('type_assessment')->comment('1: Success, 2: Error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('risk_assessment', function (Blueprint $table) {
            $table->dropColumn(['type_assessment', 'status']);
        });
    }
};
