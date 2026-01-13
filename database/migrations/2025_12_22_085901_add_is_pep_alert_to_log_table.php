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

            if (Schema::hasColumn('alert', 'is_pep')) {
                $table->dropColumn('is_pep');
            }

            if (Schema::hasColumn('alert', 'is_sanctioned')) {
                $table->dropColumn('is_sanctioned');
            }

            if (Schema::hasColumn('alert', 'is_reported')) {
                $table->dropColumn('is_reported');
            }

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alert', function (Blueprint $table) {
            // recria apenas se precisar de rollback
            $table->boolean('is_pep')->nullable()
                ->comment('Indica se a entidade é Politically Exposed Person');

            $table->boolean('is_sanctioned')->nullable()
                ->comment('Indica se a entidade consta em listas de sanções');

            $table->boolean('is_reported')->nullable()
                ->comment('Detalhes de reporte: comunicação, data, entidade reguladora');
        });
    }
};
