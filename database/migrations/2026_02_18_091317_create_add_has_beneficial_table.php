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
        Schema::table('indicator_type', function (Blueprint $table) {
            $table->boolean('has_beneficial')
                ->default(false)
                ->comment('Indica se têm beneficiário');
        });
    }

    public function down(): void
    {
        Schema::table('indicator_type', function (Blueprint $table) {
            $table->dropColumn('has_beneficial');
        });
    }

    /**
     * Reverse the migrations.
     */
  
};
