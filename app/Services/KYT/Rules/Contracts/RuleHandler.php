<?php

namespace App\Services\KYT\Rules\Contracts;

use App\Models\Entities\Entities;
use App\Models\KYT\KytRule;

interface RuleHandler
{
    /**
     * @param Entities $customer
     * @param KytRule $rule
     * @param array $policies Array de policies normalizadas
     * @param array $changes
     * @param array $refunds
     * @param array $receipts
     * @param array $beneficiaries
     * @return array|null Retorna dados do alerta [name, description, severity, score] ou null se nao aplicar
     */
    public function check(
        Entities $customer,
        KytRule $rule,
        array $policies,
        array $changes = [],
        array $refunds = [],
        array $receipts = [],
        array $beneficiaries = []
    ): ?array;
}
