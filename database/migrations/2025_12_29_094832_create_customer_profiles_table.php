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
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->integer('entity_id');
            $table->decimal('avg_transaction_amount', 20, 2)->default(0);
            $table->decimal('std_transaction_amount', 20, 2)->default(0);
            $table->integer('avg_transactions_per_month')->default(0);
            $table->integer('early_redemptions')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_profiles');
    }
};
