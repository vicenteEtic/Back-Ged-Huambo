<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apol_anulada_estorno', function (Blueprint $table) {

            // Charset
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            // 🔑 Identificação
            $table->string('n_apolice', 50)->index();
            $table->string('idtitular')->nullable()->index();

            // 📅 Datas
            $table->date('data_anulacao')->nullable();
            $table->date('data_pagamento')->nullable();

            // 📄 Motivos
            $table->string('razao')->nullable();
            $table->string('subrazao')->nullable();

            // 💰 Financeiro
            $table->string('recibo_estorno', 90)->nullable();
            $table->decimal('valor_total', 18, 2)->default(0);

            // 📊 Situação
            $table->string('situacao', 50)->nullable();

            // 🕒 Auditoria
            $table->timestamps();

            // 🚀 Índices úteis para análise
            $table->index(['n_apolice', 'data_anulacao']);
            $table->index(['situacao', 'data_pagamento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apol_anulada_estorno');
    }
};