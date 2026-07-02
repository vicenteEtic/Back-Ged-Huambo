<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('institution_entry_date')->nullable()->after('effective_date');
            $table->string('category')->nullable()->after('institution_entry_date');
            $table->string('career_regime')->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['institution_entry_date', 'category', 'career_regime']);
        });
    }
};
