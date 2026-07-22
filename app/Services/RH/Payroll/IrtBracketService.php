<?php

namespace App\Services\RH\Payroll;

use App\Repositories\RH\Payroll\IrtBracketRepository;
use App\Services\AbstractService;

class IrtBracketService extends AbstractService
{
    public function __construct(IrtBracketRepository $repository)
    {
        parent::__construct($repository);
    }
}
