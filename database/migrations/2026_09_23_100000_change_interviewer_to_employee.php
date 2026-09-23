<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            $table->dropForeign(['interviewer_id']);
        });

        // Converte os valores antigos (user_id) para employee_id através de employees.user_id.
        $interviews = DB::table('interviews')
            ->whereNotNull('interviewer_id')
            ->get(['id', 'interviewer_id']);

        foreach ($interviews as $interview) {
            $employeeId = DB::table('employees')
                ->where('user_id', $interview->interviewer_id)
                ->value('id');

            DB::table('interviews')
                ->where('id', $interview->id)
                ->update(['interviewer_id' => $employeeId]);
        }

        Schema::table('interviews', function (Blueprint $table) {
            $table->foreign('interviewer_id')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            $table->dropForeign(['interviewer_id']);
        });

        $interviews = DB::table('interviews')
            ->whereNotNull('interviewer_id')
            ->get(['id', 'interviewer_id']);

        foreach ($interviews as $interview) {
            $userId = DB::table('employees')
                ->where('id', $interview->interviewer_id)
                ->value('user_id');

            DB::table('interviews')
                ->where('id', $interview->id)
                ->update(['interviewer_id' => $userId]);
        }

        Schema::table('interviews', function (Blueprint $table) {
            $table->foreign('interviewer_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }
};
