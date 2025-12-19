<?php

namespace App\Services\Entities;


use App\Enum\FormEstablishment;
use App\Enum\StatusResidence;

class RiskScoreCalculator
{
    public function calculate($assessment, $formula, float $productRisk, float $beneficialScore): float
    {
        $scores = $this->baseScores($assessment, $productRisk);

        return $formula->entity_type == 2
            ? $this->individual($scores, $formula)
            : $this->corporate($scores, $formula, $beneficialScore);
    }

    private function baseScores($a, float $productRisk): array
    {
        return [
            'identification' => $a->indetificationCapacity?->first()?->score ?? 0,
            'profession'     => $a->profession?->first()?->score ?? 0,
            'nationality'    => $a->nationlity?->first()?->score ?? 0,
            'country'        => $a->countryResidence?->first()?->score ?? 0,
            'channel'        => $a->channel?->first()?->score ?? 0,
            'status'         => $a->status_residence == StatusResidence::RESIDENTE ? 1 : 3,
            'establishment'  => $a->form_establishment == FormEstablishment::PRESENCIAL ? 1 : 3,
            'pep'            => $a->pep ? 3 : 0,
            'sanction'       => $a->santion ? 20 : 0,
            'process'        => $a->processesReportedAuthoritie ? 3 : 0,
            'product'        => $productRisk,
        ];
    }

    private function individual(array $s, $f): float
    {
        return
            $s['identification'] * $f->identification_capacity +
            $s['profession']     * $f->profession +
            $s['nationality']    * $f->nationality +
            $s['country']        * $f->country_residence +
            $s['status']         * $f->status_residence +
            $s['process']        * $f->processesReportedAuthoritie +
            $s['pep']            * $f->pep +
            $s['sanction']       * $f->santion +
            $s['channel']        * $f->channel +
            $s['product']        * $f->product_risk;
    }

    private function corporate(array $s, $f, float $beneficial): float
    {
        return
            $s['identification'] * $f->identification_capacity +
            $s['profession']     * $f->profession +
            $s['country']        * $f->country_residence +
            $s['status']         * $f->status_residence +
            $beneficial          * $f->beneficialOwner +
            $s['process']        * $f->processesReportedAuthoritie +
            $s['sanction']       * $f->santion +
            $s['channel']        * $f->channel +
            $s['product']        * $f->product_risk;
    }
}
