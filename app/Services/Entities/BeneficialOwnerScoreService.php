<?php

namespace App\Services\Entities;
class BeneficialOwnerScoreService
{
    public function calculate(array $owners): int
    {
        return collect($owners)
            ->filter(fn ($o) => !empty($o->pep))
            ->count() * 3;
    }
}
