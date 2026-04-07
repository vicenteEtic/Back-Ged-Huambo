<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
    {
        Schema::table('diligence', function (Blueprint $table) {
            // Alterar as colunas max e min de float para string
            $table->decimal('max', 10, 2)->change();
            $table->decimal('min', 10, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diligence', function (Blueprint $table) {
            // Reverter para float caso faça rollback
            $table->decimal('max', 10, 2)->change();
            $table->decimal('min', 10, 2)->change();
        });
    }
};
