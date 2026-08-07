<?php

namespace Database\Seeders;

use App\Enum\DeclarationTypeEnum;
use App\Models\RH\Declaration\DeclarationType;
use Illuminate\Database\Seeder;

class DeclarationTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DeclarationTypeEnum::cases() as $case) {
            DeclarationType::updateOrCreate(
                ['code' => $case->value],
                [
                    'name' => $case->label(),
                    'description' => $case->description(),
                    'requires_approval' => $this->requiresApproval($case->value),
                    'is_active' => true,
                ]
            );
        }
    }

    private function requiresApproval(string $code): bool
    {
        return in_array($code, [
            'vencimento',
            'ausencia_disciplinar',
            'aposentacao',
            'compatibilidade',
        ]);
    }
}
