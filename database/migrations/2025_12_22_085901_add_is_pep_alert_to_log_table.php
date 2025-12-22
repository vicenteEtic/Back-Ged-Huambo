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
        Schema::table('alert', function (Blueprint $table) {
                  $table->boolean('is_pep')->nullable()->default(false)->comment('Indica se a entidade é Politically Exposed Person');
            $table->boolean('is_sanctioned')->nullable()->default(false)->comment('Indica se a entidade consta em listas de sanções');
            $table->boolean('is_reported')->nullable()->default(false)->comment('Detalhes de reporte: comunicação, data, entidade reguladora');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alert', function (Blueprint $table) {

        });
    }
};
