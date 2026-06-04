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
        // Alterando a tabela risk_formula
        Schema::table('risk_formula', function (Blueprint $table) {
            $table->float('form_establishment')->nullable()->change();
        });

        // Alterando a tabela risk_assessment
        Schema::table('risk_assessment', function (Blueprint $table) {
            $table->float('form_establishment')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertendo as alterações da tabela risk_formula (voltando ao padrão original com default 1)
        Schema::table('risk_formula', function (Blueprint $table) {
            $table->float('form_establishment')->default(1)->nullable(false)->change();
        });

        // Revertendo as alterações da tabela risk_assessment
        Schema::table('risk_assessment', function (Blueprint $table) {
            $table->float('form_establishment')->nullable(false)->change();
        });
    }
};
