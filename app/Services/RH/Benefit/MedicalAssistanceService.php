<?php

namespace App\Services\RH\Benefit;

use App\Repositories\RH\Benefit\MedicalAssistanceRepository;
use App\Services\AbstractService;

class MedicalAssistanceService extends AbstractService
{
    public function __construct(MedicalAssistanceRepository $repository)
    {
        parent::__construct($repository);
    }
}
