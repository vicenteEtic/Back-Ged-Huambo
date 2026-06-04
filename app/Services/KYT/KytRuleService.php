<?php

namespace App\Services\KYT;

use App\Repositories\KYT\KytRuleRepository;
use App\Services\AbstractService;

class KytRuleService extends AbstractService
{
    public function __construct(KytRuleRepository $repository)
    {
        parent::__construct($repository);
    }
}
