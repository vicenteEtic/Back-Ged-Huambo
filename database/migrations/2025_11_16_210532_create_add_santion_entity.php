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
        Schema::table('risk_assessment', function (Blueprint $table) {
            $table->string('processesReportedAuthoritie')
            
                ->nullable();

            $table->string('beneficialOwner')
             
                ->nullable();
             
                $table->string('santion')
             
                ->nullable();
                
        });
    }

    /**
     * Reverse the migrations.
     */
   
};
