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
            $table->float('identification_capacity')->default(1);
            $table->float('form_establishment')->default(1);
            $table->float('category')->default(1);
            $table->float('status_residence')->default(1);
            $table->float('profession')->default(1);
            $table->float('pep')->default(1);
            $table->float('country_residence')->default(1);
            $table->float('nationality')->default(1);
            $table->float('entity_type');
            $table->float('channel')->default(1);
            $table->float('product_risk')->default(1);
            $table->float('santion')->default(1);
            $table->float('distributionChannel')->default(1);
            $table->float('beneficialOwner')->default(1);
            $table->float('processesReportedAuthoritie')->default(1);
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
