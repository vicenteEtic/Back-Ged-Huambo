<?php
namespace App\Services\Transation;

use App\Repositories\Transation\transaionControlRepository;
use App\Services\AbstractService;

class transaionControlService extends AbstractService
{
    public function __construct(transaionControlRepository $repository)
    {
        parent::__construct($repository);
    }
}