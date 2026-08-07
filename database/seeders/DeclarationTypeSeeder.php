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
                    'requires_approval' => $case->requiresApproval(),
                    'is_active' => true,
                ]
            );
        }
    }
}
