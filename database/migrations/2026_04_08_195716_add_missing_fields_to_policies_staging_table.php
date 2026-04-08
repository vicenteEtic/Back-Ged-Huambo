<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('policies_staging', function (Blueprint $table) {

            // 🧩 Estrutura de negócio
            $table->string('codigo_ramo', 50)->nullable();
            $table->string('descricao_ramo')->nullable();

            $table->string('codigo_produto', 50)->nullable();

            $table->string('codigo_canal', 50)->nullable();
            $table->string('descricao_canal')->nullable();

            $table->string('codigo_agente', 50)->nullable();
            $table->string('descricao_agente')->nullable();


            // 📅 Datas adicionais
            $table->date('data_proximo_vencimento')->nullable();
            $table->date('data_anulacao')->nullable();


            // 💰 Financeiros
            $table->string('moeda', 10)->nullable();

            $table->decimal('capital_liquido_cosseguro', 18, 2)->default(0);
            $table->decimal('premio_simples', 18, 2)->default(0);

            $table->decimal('encargos', 18, 2)->default(0);
            $table->decimal('outros_encargos', 18, 2)->default(0);


            // 📄 Controlo / auditoria
            $table->string('numero_acta', 50)->nullable();

            $table->string('motivo_anulacao')->nullable();
            $table->string('submotivo_anulacao')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('policies_staging', function (Blueprint $table) {

            $table->dropColumn([
                'codigo_ramo',
                'descricao_ramo',
                'codigo_produto',
                'codigo_canal',
                'descricao_canal',
                'codigo_agente',
                'descricao_agente',
                'data_proximo_vencimento',
                'data_anulacao',
                'moeda',
                'capital_liquido_cosseguro',
                'premio_simples',
                'encargos',
                'outros_encargos',
                'numero_acta',
                'motivo_anulacao',
                'submotivo_anulacao'
            ]);
        });
    }
};