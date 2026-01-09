<?php
namespace App\Services\Aml\Rules;

use App\Models\Transation\Transation;
use App\Services\Aml\Dto\AmlAlertDto;

class SmurfingRule
{
    public function apply(array $tx): array
    {
        $count = Transation::where('client_id', $tx['client_id'])
            ->where('amount', '<', 100_000)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        if ($count >= 10) {
            return [
                new AmlAlertDto(
                    'SMURFING',
                    'high',
                    'Possível fracionamento para evasão de limites',
                    8
                )
            ];
        }

        return [];
    }
}
