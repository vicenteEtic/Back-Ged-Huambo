<?php
namespace App\Services\Transation;

use App\Repositories\Transation\PoliciesRepository;
use App\Services\AbstractService;

class PoliciesService extends AbstractService
{
    public function __construct(PoliciesRepository $repository)
    {
        parent::__construct($repository);
    }
}