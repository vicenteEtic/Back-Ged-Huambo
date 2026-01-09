<?php
namespace App\Services\Aml\Dto;

class AmlAlertDto
{
    public function __construct(
        public string $ruleCode,
        public string $severity,
        public string $reason,
        public int    $riskScore
    ) {}
}
