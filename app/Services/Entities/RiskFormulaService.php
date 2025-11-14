<?php
namespace App\Services\Entities;

use App\Repositories\Entities\RiskFormulaRepository;
use App\Services\AbstractService;

class RiskFormulaService extends AbstractService
{
    public function __construct(RiskFormulaRepository $repository)
    {
        parent::__construct($repository);
    }
}