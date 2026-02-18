<?php
namespace App\Services\Entities;

use App\Repositories\Entities\BeneficialRepository;
use App\Services\AbstractService;

class BeneficialService extends AbstractService
{
    public function __construct(BeneficialRepository $repository , private readonly PepService $pepService)
    {
        parent::__construct($repository);
    }


     public function createBeneficial(array $data, int $riskAssessmentId): void
    {
        foreach ($data['beneficial'] as $owner) {
            $dataBeneficial['risk_assessment_id'] = $riskAssessmentId;
               $dataBeneficial['name'] =$owner['name'];
               $dataBeneficial['nationality'] =$owner['nationality'];
               $dataBeneficial['is_pep'] =$owner['is_pep'];
               $dataBeneficial['is_sanctioned'] =$owner['is_sanctioned'];
               $dataBeneficial['processesReportedAuthoritie'] =$owner['processesReportedAuthoritie'];
            $this->storeOrUpdate($dataBeneficial, $dataBeneficial);
          
            if ($owner['is_pep']) {
                $pepData = [
                    "name" => $owner['name'] ?? null,
                ];
    
                $this->pepService->storeOrUpdate($pepData, $pepData);
              
            }
        }
    }
}