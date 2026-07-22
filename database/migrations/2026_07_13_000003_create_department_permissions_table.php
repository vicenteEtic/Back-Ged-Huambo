<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('permission_id')->constrained('permission')->cascadeOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_permissions');
    }
};
