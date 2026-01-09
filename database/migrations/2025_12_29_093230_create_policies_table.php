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
        Schema::create('policies', function (Blueprint $table) {
            $table->integer('entity_id');
            $table->string('contract_number');
           $table->string('control_id')->nullable();
            $table->string('product');
            $table->string('channel')->nullable();
            $table->string('agent')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('renewal_date')->nullable();
            $table->decimal('capital', 20, 2)->nullable();
            $table->decimal('premium_simple', 20, 2)->nullable();
            $table->decimal('premium_total', 20, 2)->nullable();
            $table->decimal('charges', 20, 2)->nullable();
            $table->decimal('interest', 20, 2)->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policies');
    }
};
