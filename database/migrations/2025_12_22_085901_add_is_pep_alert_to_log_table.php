<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
    {
        Schema::table('alert', function (Blueprint $table) {
            $table->boolean('is_pep')->nullable()
                ->comment('Indica se a entidade é Politically Exposed Person');

            $table->boolean('is_sanctioned')->nullable()
                ->comment('Indica se a entidade consta em listas de sanções');

            $table->boolean('is_reported')->nullable()
                ->comment('Detalhes de reporte: comunicação, data, entidade reguladora');
        });
    }

    public function down(): void
    {
        Schema::table('alert', function (Blueprint $table) {
            $table->dropColumn([
                'is_pep',
                'is_sanctioned',
                'is_reported',
            ]);
        });
    }
};
