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
        Schema::create('entities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('social_denomination')->nullable();
            $table->string('customer_number')->unique()->nullable();
            $table->string('policy_number')->nullable();
            $table->tinyInteger('entity_type')->nullable();
            $table->string('color')->nullable();
            $table->string('risk_level')->nullable();
            $table->string('diligence')->nullable();
            $table->timestamp('last_evaluation')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};
