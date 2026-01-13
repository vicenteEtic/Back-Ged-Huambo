<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columnsToDrop = [];

        if (Schema::hasColumn('alert', 'is_pep')) {
            $columnsToDrop[] = 'is_pep';
        }

        if (Schema::hasColumn('alert', 'is_sanctioned')) {
            $columnsToDrop[] = 'is_sanctioned';
        }

        if (Schema::hasColumn('alert', 'is_reported')) {
            $columnsToDrop[] = 'is_reported';
        }

        if (!empty($columnsToDrop)) {
            Schema::table('alert', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }

    public function down(): void
    {
        Schema::table('alert', function (Blueprint $table) {

            if (!Schema::hasColumn('alert', 'is_pep')) {
                $table->boolean('is_pep')->nullable()
                    ->comment('Indica se a entidade é Politically Exposed Person');
            }

            if (!Schema::hasColumn('alert', 'is_sanctioned')) {
                $table->boolean('is_sanctioned')->nullable()
                    ->comment('Indica se a entidade consta em listas de sanções');
            }

            if (!Schema::hasColumn('alert', 'is_reported')) {
                $table->boolean('is_reported')->nullable()
                    ->comment('Detalhes de reporte: comunicação, data, entidade reguladora');
            }
        });
    }
};
