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
        Schema::create('risk_formula', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('identification_capacity')->default(1);
            $table->string('form_establishment')->default(1);
            $table->string('category')->default(1);
            $table->string('status_residence')->default(1);
            $table->string('profession')->default(1);
            $table->string('pep')->default(1);
            $table->string('country_residence')->default(1);
            $table->string('nationality')->default(1);
            $table->string('entity_type');
            $table->string('channel')->default(1);
            $table->string('product_risk')->default(1);
            $table->string('santion')->default(1);
            $table->string('distributionChannel')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risk_formula');
    }
};
