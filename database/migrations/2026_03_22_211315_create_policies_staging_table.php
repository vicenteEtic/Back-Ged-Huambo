<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policies_staging', function (Blueprint $table) {
            $table->id();

            // 🔑 Dados principais
            $table->unsignedBigInteger('numero_cliente')->index();
            $table->string('numero_apolice', 50)->index();

            // 📄 Informações da apólice
            $table->string('descricao_produto')->nullable();
            $table->string('estado_apolice', 50)->nullable();

            // 📅 Datas
            $table->string('data_inicio', 50)->nullable();
            $table->string('data_fim', 50)->nullable();

            // 💰 Valores
            $table->decimal('capital', 18, 2)->default(0);
            $table->decimal('premium_total', 18, 2)->default(0);
            $table->decimal('interest', 18, 2)->default(0);

            $table->timestamps();

            // 🚀 Índice para performance
            $table->index(['numero_cliente', 'numero_apolice']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policies_staging');
    }
};