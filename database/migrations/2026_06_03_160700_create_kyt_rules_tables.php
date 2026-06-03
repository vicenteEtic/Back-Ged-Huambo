<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('kyt_rule_definition_products');
        Schema::dropIfExists('kyt_rule_definitions');

        Schema::create('kyt_rule_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->index()->comment('ex: frequent_beneficiary_changes');
            $table->string('name');
            $table->string('entity_type'); // 'individual', 'collective', 'both'
            $table->string('threshold_field')->nullable()->comment('Coluna alvo: premium_total, refund_amount, capital, etc');
            $table->decimal('threshold_value', 15, 2)->nullable();
            $table->integer('min_events')->nullable();
            $table->integer('max_days')->nullable();
            $table->integer('score_base')->default(20);
            $table->json('score_increments')->nullable()->comment('Condicoes: {"events_above_min":10, "half_window":5}');
            $table->string('severity')->default('Alto');
            $table->text('description_template');
            $table->text('interpretation_aml');
            $table->json('extra_params')->nullable()->comment('Parametros especificos da regra');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['slug', 'entity_type']);
        });

        Schema::create('kyt_rule_definition_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kyt_rule_definition_id')->constrained('kyt_rule_definitions')->cascadeOnDelete();
            $table->string('product_name');
            $table->enum('type', ['relevant', 'excluded']);
            $table->timestamps();

            $table->unique(['kyt_rule_definition_id', 'product_name', 'type'], 'krdp_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyt_rule_definition_products');
        Schema::dropIfExists('kyt_rule_definitions');
    }
};
