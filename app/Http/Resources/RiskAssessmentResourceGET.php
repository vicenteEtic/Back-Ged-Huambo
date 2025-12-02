<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RiskAssessmentResourceGET extends JsonResource
{
     public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['category'] = $this->safeIndicator($data['category'] ?? null);
        $data['profession'] = $this->safeIndicator($data['profession'] ?? null);
        $data['country_residence'] = $this->safeIndicator($data['country_residence'] ?? null);
        $data['channel'] = $this->safeIndicator($data['channel'] ?? null);

        // Nationality pode vir INT ou OBJ
        $data['nationality'] = $this->safeIndicator($data['nationality'] ?? null);

        // Indetification
        $data['indetification_capacity'] = $this->safeIndicator($data['indetification_capacity'] ?? null);

        // Nationlity (relacionamento)
        $data['nationlity'] = $this->safeIndicator($data['nationlity'] ?? null);

        // Product risk
        $data['product_risk'] = $this->safeProductRisk($data['product_risk'] ?? []);

        // Beneficial owners - deixar como está
        // Risk formula - deixar como está

        return $data;
    }

    private function safeIndicator($value)
    {
        if (is_array($value)) {
            return [
                'score' => $value['score'] ?? null,
                'description' => $value['description'] ?? null,
            ];
        }

        // se vier int/string
        return [
            'score' => null,
            'description' => $value,
        ];
    }

    private function safeProductRisk($items)
    {
        if (!is_array($items)) {
            return [];
        }

        return array_map(function ($item) {
            if (!is_array($item)) {
                return null;
            }

            return [
                'product' => [
                    'score' => $item['score'] ?? null,
                    'description' => $item['product']['description'] ?? null,
                ]
            ];
        }, $items);
    }
}
