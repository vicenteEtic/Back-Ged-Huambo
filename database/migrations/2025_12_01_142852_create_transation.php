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
        Schema::create('transation', function (Blueprint $table) {
            $table->id();
            $table->string('entite_id');
       
            $table->decimal('amount', 15, 2);
            $table->string('currency', 5)->default('AOA');
            $table->dateTime('date');
            $table->string('type');
            $table->string('status');
            $table->string('channel');
            $table->string('description')->nullable();
            $table->string('category')->nullable();
            $table->integer('risk_score')->default(0);
            $table->string('ip_address')->nullable();
            $table->string('device')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transation');
    }
};
