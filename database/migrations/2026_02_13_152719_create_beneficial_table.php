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
        Schema::create('beneficial', function (Blueprint $table) {
            $table->id();
            $table->string('name');

             $table->unsignedBigInteger('risk_assessment_id');
            $table->string('nationality')->nullable();
                $table->boolean('is_pep')->nullable()
                ->comment('Indica se a entidade é Politically Exposed Person');

            $table->boolean('is_sanctioned')->nullable()
                ->comment('Indica se a entidade consta em listas de sanções');


            $table->boolean('processesReportedAuthoritie')->nullable()
                ->comment('Indica se a entidade processesReportedAuthoritie');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficial');
    }
};
