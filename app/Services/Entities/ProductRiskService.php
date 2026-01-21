<?php
namespace App\Services\Entities;

use App\Repositories\Entities\ProductRiskRepository;
use App\Services\AbstractService;

class ProductRiskService extends AbstractService
{
    public function __construct(ProductRiskRepository $repository)
    {
        parent::__construct($repository);
    }

    public function storeProductRisks($riskProducts, $riskAssessmentId): void
    {
        foreach ($riskProducts->get() as $product) {
            $productRisk = [
                'product_id' => $product->id,
                'score' => $product->score,
                'risk_assessment_id' => $riskAssessmentId
            ];
            $this->repository->store(
                $productRisk
            );
        }
    }


    public function showProduct($data){
     return    $this->repository->showProduct($data
            );
    }
}