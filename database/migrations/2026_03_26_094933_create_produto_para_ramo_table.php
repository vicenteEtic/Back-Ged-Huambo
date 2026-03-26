<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produto_para_ramo', function (Blueprint $table) {
            $table->id();
            $table->string('descricao_produto')->unique();
            $table->string('ramo');
            $table->timestamps();

            // define charset e collation corretos
           // define charset e collation para toda a tabela
           $table->charset = 'utf8mb4';
           $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produto_para_ramo');
    }
};