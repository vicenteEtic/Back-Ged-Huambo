<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RiskAssessmentResource extends JsonResource
{
    public function toArray($request)
    {
        // Converte todo o modelo em array primeiro
        $data = parent::toArray($request);

        // Category
        $data['category'] = isset($data['category']) && is_array($data['category'])
            ? [
                'score' => $data['category']['score'] ?? null,
                'description' => $data['category']['description'] ?? null,
            ]
            : null;

        // Profession
        $data['profession'] = isset($data['profession']) && is_array($data['profession'])
            ? [
                'score' => $data['profession']['score'] ?? null,
                'description' => $data['profession']['description'] ?? null,
            ]
            : null;

        // Country Residence
        $data['country_residence'] = isset($data['country_residence']) && is_array($data['country_residence'])
            ? [
                'score' => $data['country_residence']['score'] ?? null,
                'description' => $data['country_residence']['description'] ?? null,
            ]
            : null;

        // channel
        $data['channel'] = isset($data['channel']) && is_array($data['channel'])
            ? [
                'score' => $data['channel']['score'] ?? null,
                'description' => $data['channel']['description'] ?? null,
            ]
            : null;
        $data['indetification_capacity'] = isset($data['indetification_capacity']) && is_array($data['indetification_capacity'])
            ? [
                'score' => $data['indetification_capacity']['score'] ?? null,
                'description' => $data['indetification_capacity']['description'] ?? null,
            ]
            : null;



        // channel
        $data['entity'] = isset($data['entity']) && is_array($data['entity'])
            ? [

                "id" => $data['entity']['id'] ?? null,
                "nif" => $data['entity']['nif'] ?? null,
                "social_denomination" => $data['entity']['social_denomination'] ?? null,
                "customer_number" => $data['entity']['customer_number'] ?? null,
                "policy_number" => $data['entity']['policy_number'] ?? null,
                "entity_type" => $data['entity']['entity_type'] ?? null,
                "color" => $data['entity']['color'] ?? null,
                "risk_level" => $data['entity']['risk_level'] ?? null,
                "diligence" => $data['entity']['diligence'] ?? null,
                "last_evaluation" => $data['entity']['last_evaluation'] ?? null,
            ]
            : null;

        // channel
        $data['risk_formula'] = isset($data['risk_formula']) && is_array($data['risk_formula'])
            ? [
                "name" => $data['risk_formula']['name'] ?? null,
                "identification_capacity" => $data['risk_formula']['identification_capacity'] ?? null,
                "form_establishment" => $data['risk_formula']['form_establishment'] ?? null,
                "category" => $data['risk_formula']['category'] ?? null,
                "status_residence" => $data['risk_formula']['status_residence'] ?? null,
                "profession" => $data['risk_formula']['profession'] ?? null,
                "pep" => $data['risk_formula']['pep'] ?? null,
                "country_residence" => $data['risk_formula']['country_residence'] ?? null,
                "nationality" => $data['risk_formula']['nationality'] ?? null,

                "channel" => $data['risk_formula']['channel'] ?? null,
                "product_risk" => $data['risk_formula']['product_risk'] ?? null,
                "santion" => $data['risk_formula']['santion'] ?? null,
                "distributionChannel" => $data['risk_formula']['distributionChannel'] ?? null,
                "beneficialOwner" => $data['risk_formula']['beneficialOwner'] ?? null,
                "processesReportedAuthoritie" => $data['risk_formula']['processesReportedAuthoritie'] ?? null,
            ]
            : null;


        // Aplicando regra para product_risk
        if (isset($data['product_risk']) && is_array($data['product_risk'])) {
            $data['product_risk'] = array_map(function ($item) {
                return [
                 "product" =>[
                    'score' => $item['score'] ?? null,
                    'description' => $item['product']['description'] ?? null,
                 ]
                
                ];
            }, $data['product_risk']);
        }


        $data['user'] = isset($data['user']) && is_array($data['user'])
            ? [

                "first_name" => $data['user']['first_name'] ?? null,
                "last_name" => $data['user']['last_name'] ?? null


            ]
            : null;
        // Nationality
        if (isset($data['nationlity'])) {
            if (is_array($data['nationlity'])) {
                $data['nationlity'] = [
                    'score' => $data['nationlity']['score'] ?? null,
                    'description' => $data['nationlity']['description'] ?? null,
                ];
            } else {
                // Se for apenas um ID ou outro tipo
                $data['nationlity'] = [
                    'score' => null,
                    'description' => $data['nationlity'],
                ];
            }
        } else {
            $data['nationlity'] = null;
        }
        // Remover type_assessment
        unset($data['deleted_at'], $data['risk_assessment_control_id'],$data['entity_id'],$data['type_assessment']);
        return $data;
    }
}
