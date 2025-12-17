<?php
namespace App\Services\Aml\Rules;

use App\Models\Transation\Transation;
use App\Services\Aml\Dto\AmlAlertDto;
use App\Services\Indicator\IndicatorTypeService;

class ProductLimitRule
{
    public function apply(array $tx): array
    {
        $alerts = [];

        $indicatorService = app(IndicatorTypeService::class);
        $product = $indicatorService->getByDescription($tx['product_code'] ?? null);

        if (!$product) {
            return [];
        }

        $total = Transation::where('client_id', $tx['client_id'])
            ->where('product_code', $tx['product_code'])
            ->sum('amount');

        // Exemplo: limite dinâmico pelo risco do produto
        $limit = match ($product->risk) {
            'Baixo' => 5_000_000,
            'Médio' => 2_000_000,
            'Alto', 'Muito Alto' => 500_000,
            default => 1_000_000
        };

        if ($total > $limit) {
            $alerts[] = new AmlAlertDto(
                ruleCode: 'PRODUCT_LIMIT',
                severity: 'high',
                reason: "Exposição excessiva ao produto {$product->description}",
                riskScore: $product->score + 2
            );
        }

        return $alerts;
    }
}
