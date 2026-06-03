<?php

namespace App\Services\KYT\Rules\Contracts;

use App\Models\Entities\Entities;
use App\Models\KYT\KytRule;

interface RuleHandler
{
    /**
     * @param Entities $customer
     * @param KytRule $rule
     * @param array $policies
     * @param array $changes
     * @param array $refunds
     * @param array $receipts
     * @param array $beneficiaries
     * @return array Lista de alertas. Cada alerta: ['name','description','severity','score']. Array vazio = sem alerta.
     */
    public function check(
        Entities $customer,
        KytRule $rule,
        array $policies,
        array $changes = [],
        array $refunds = [],
        array $receipts = [],
        array $beneficiaries = []
    ): array;
}
