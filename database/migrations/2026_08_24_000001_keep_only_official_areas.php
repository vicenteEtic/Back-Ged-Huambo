<?php

use App\Models\RH\Area\Area;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const KEEP = [
        'SEC-GERAL',
        'GAB-JUR',
        'GAB-COM',
        'GAB-RH',
        'GAB-GOV',
        'VICE-PSE',
        'VICE-STI',
    ];

    public function up(): void
    {
        Area::whereNotIn('code', self::KEEP)->get()->each->delete();
    }

    public function down(): void
    {
        Area::withTrashed()->whereNotIn('code', self::KEEP)->restore();
    }
};
