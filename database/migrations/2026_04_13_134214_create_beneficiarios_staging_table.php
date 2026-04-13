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
        Schema::create('beneficiarios_staging', function (Blueprint $table) {
            $table->id();
            $table->string('numero_apolice')->nullable()->index();
            $table->string('codigo_produto')->nullable();
            $table->string('descricao_produto')->nullable();
            $table->string('codigo_beneficiario')->nullable();
            $table->string('nome_beneficiario')->nullable();
            $table->string('tipo_beneficiario')->nullable();
            $table->string('percentagem_atribuida')->nullable();
            $table->string('pais_residencia_beneficiario')->nullable();
            $table->string('parentesco_beneficiario')->nullable();
            $table->string('codigo_situacao_apolice')->nullable();
            $table->string('situacao_apolice')->nullable();
            $table->string('data_atualizacao_beneficiario')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficiarios_staging');
    }
};
