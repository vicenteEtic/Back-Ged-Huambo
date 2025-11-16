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
        Schema::table('beneficial_owner', function (Blueprint $table) {
            $table->string('nationality')->nullable()->after('updated_at');
            $table->string('percentage')->nullable()->after('nationality');
            $table->boolean('is_legal_representative')->nullable()->after('percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('beneficial_owner', function (Blueprint $table) {
            //
        });
    }
};
