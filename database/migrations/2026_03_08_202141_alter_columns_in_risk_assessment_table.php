<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('risk_assessment', function (Blueprint $table) {
            
            // renomear coluna
            $table->renameColumn('category', 'categoryP');
            $table->renameColumn('profession', 'professionP');

        });
    }

    public function down()
    {
        Schema::table('risk_assessment', function (Blueprint $table) {

            // voltar nome original
            $table->renameColumn('categoryP', 'category');

            $table->renameColumn('profession',  'professionP');
        });
    }
};
