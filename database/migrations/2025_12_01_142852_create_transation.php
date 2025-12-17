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
            $table->string('transaction_uid');
            $table->dateTime('transaction_date');
            $table->string('transaction_type');
            $table->decimal('amount', 15, 2);
            $table->string('currency');
            $table->string('payment_channel');
            $table->string('origin_account')->nullable();
            $table->string('destination_account')->nullable();
            $table->string('status');

            // 2. Associação
            $table->string('client_id');
            $table->string('policy_number');
            $table->string('product_code');
            $table->string('beneficiary_id')->nullable();

            // Risco
            $table->integer('risk_score')->default(0);

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
