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
            $table->id();
    
            $table->string('contract_number')->unique();     
            $table->string('product_code')->nullable();      
            $table->string('product_desc')->nullable();      
            $table->string('branch_code')->nullable();       
            $table->string('branch_desc')->nullable();       
            $table->string('channel_code')->nullable();      
            $table->string('channel_desc')->nullable();      
            $table->string('agent_code')->nullable();        
            $table->string('agent_desc')->nullable();        
            $table->string('status')->nullable();            
            $table->date('start_date')->nullable();          
            $table->date('end_date')->nullable();            
            $table->date('next_renewal_date')->nullable();   
            $table->date('next_expiry_date')->nullable();    
            $table->string('currency')->nullable();          
            $table->decimal('capital', 20, 2)->nullable();   
            $table->decimal('capital_cosign', 20, 2)->nullable(); 
            $table->decimal('premium_simple', 20, 2)->nullable(); 
            $table->decimal('premium_total', 20, 2)->nullable();  
            $table->decimal('charges', 20, 2)->nullable();        
            $table->decimal('other_charges', 20, 2)->nullable();  
            $table->decimal('interest', 20, 2)->nullable();       
         
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
