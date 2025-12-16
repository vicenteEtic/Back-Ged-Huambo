<?php

namespace App\Repositories\Diligence;

use App\Models\Diligence\Diligence;
use App\Repositories\AbstractRepository;

class DiligenceRepository extends AbstractRepository
{
    public function __construct(Diligence $model)
    {
        parent::__construct($model);
    }

    public function getDilligenceAssessment($riskValue)
    {
        $result = $this->model
            ->where('min', '<=', $riskValue)
            ->where('max', '>=', $riskValue)
            ->first();
    
        // Se não encontrar no ranking, pega o último elemento
        if (!$result) {
            return $this->model
                ->orderBy('max', 'desc')
                ->first();
        }
    
        return $result;
    }
}
