<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_changes_staging', function (Blueprint $table) {
            $table->id();

            // 🔑 Identificação
            $table->string('numero_apolice')->nullable()->index();

            // 📅 Data (usar datetime para análises temporais)
            $table->dateTime('data_alteracao')->nullable()->index();

            // 🔄 Tipo de alteração
            $table->string('tipo_alteracao')->nullable()->index();

            // 💰 Valores (NUMÉRICOS — MUITO IMPORTANTE)
            $table->decimal('valor_anterior', 18, 2)->nullable();
            $table->decimal('novo_valor', 18, 2)->nullable();
            $table->decimal('percentual_variacao', 10, 2)->nullable()->index();

            // 🧾 Motivo (não precisa de index — texto livre)
            $table->text('motivo_alteracao')->nullable();

            $table->timestamps();

            // 🔥 Índice composto (muito útil para KYT)
            $table->index(['numero_apolice', 'tipo_alteracao'], 'idx_apolice_tipo');

            // 🔥 Índice para análises temporais
            $table->index(['data_alteracao', 'percentual_variacao'], 'idx_data_variacao');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_changes_staging');
    }
};