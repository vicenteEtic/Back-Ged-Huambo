<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('attendance_request_types')
            ->where('required_documents', '!=', '[]')
            ->update(['required_documents' => '[]']);
    }

    public function down(): void
    {
        // Não reverter — documentos obrigatórios foram removidos intencionalmente.
    }
};
