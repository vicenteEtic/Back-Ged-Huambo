<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aml_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->nullable(); // ->constrained('transactions')->onDelete('set null');
            $table->string('client_id');
            $table->string('transaction_ref')->nullable();;

            $table->string('severity');
            $table->string('reason');
            $table->integer('risk_score');

            $table->string('status')->default('aberto');

            $table->foreignId('assigned_to')->nullable(); // analista
            $table->text('analyst_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aml_alerts');
    }
};
