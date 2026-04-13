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
        Schema::create('recibos_cobrados', function (Blueprint $blueprint) {
            $blueprint->id();
            
            // Relacionamento / Identificadores
            $blueprint->string('id_transacao')->nullable()->index();
            $blueprint->string('recibo')->nullable()->index();
            $blueprint->string('numero_apolice')->nullable()->index();
            
            // Dados Financeiros e Datas
            $blueprint->dateTime('data_pagamento')->nullable();
            $blueprint->decimal('valor_pago', 15, 2)->default(0);
            $blueprint->string('metodo_pagamento')->nullable();
            
            // Compliance / AML (IBAN e Origem)
            $blueprint->string('iban_origem')->nullable();
            $blueprint->string('pais_iban_origem')->nullable(); // ISO 3166-1 alpha-3
            
            // Dados do Pagador
            $blueprint->string('codigo_pagador')->nullable();
            $blueprint->string('nome_pagador')->nullable();
            $blueprint->string('nif_pagador')->nullable()->index();
            
            // Relacionamento e Indicadores de Risco (KYT)
            $blueprint->string('relacao_com_tomador')->nullable();
            $blueprint->string('indicador_pagamento_terceiro')->nullable();
            
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recibos_cobrados');
    }
};