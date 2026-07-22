<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_id')->constrained('processes')->cascadeOnDelete();
            $table->foreignId('assignment_id')->nullable()->constrained('process_assignments')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->text('comment');
            $table->string('comment_type')->default('note'); // note, opinion, correction_request, approval
            $table->string('attachment_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_comments');
    }
};
