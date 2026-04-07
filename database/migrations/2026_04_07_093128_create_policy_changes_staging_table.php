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

            $table->string('numero_apolice')->nullable()->index();
            $table->dateTime('data_alteracao')->nullable()->index();
            $table->string('tipo_alteracao')->nullable()->index();

            // ✅ NUMÉRICOS CORRETOS
            $table->decimal('valor_anterior', 15, 2)->nullable();
            $table->decimal('novo_valor', 15, 2)->nullable();
            $table->decimal('percentual_variacao', 10, 2)->nullable()->index();

            $table->text('motivo_alteracao')->nullable();

            $table->timestamps();

            $table->index(['numero_apolice', 'tipo_alteracao'], 'idx_apolice_tipo');
            $table->index(['data_alteracao', 'percentual_variacao'], 'idx_data_variacao');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_changes_staging');
    }
};