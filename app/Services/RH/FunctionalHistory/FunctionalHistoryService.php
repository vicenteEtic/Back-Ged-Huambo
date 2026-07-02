<?php

namespace App\Services\RH\FunctionalHistory;

use App\Repositories\RH\FunctionalHistory\FunctionalHistoryRepository;
use App\Services\AbstractService;

class FunctionalHistoryService extends AbstractService
{
    public function __construct(FunctionalHistoryRepository $repository)
    {
        parent::__construct($repository);
    }
}
