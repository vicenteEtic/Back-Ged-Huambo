<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['category', 'career_regime']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('category')
                ->nullable()
                ->after('institution_entry_date')
                ->constrained('positions')
                ->nullOnDelete();

            $table->foreignId('career_regime')
                ->nullable()
                ->after('category')
                ->constrained('shifts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category');
            $table->dropConstrainedForeignId('career_regime');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->string('category')->nullable()->after('institution_entry_date');
            $table->string('career_regime')->nullable()->after('category');
        });
    }
};
