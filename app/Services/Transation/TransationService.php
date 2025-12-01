<?php
namespace App\Services\Transation;

use App\Repositories\Transation\TransationRepository;
use App\Services\AbstractService;

class TransationService extends AbstractService
{
    public function __construct(TransationRepository $repository)
    {
        parent::__construct($repository);
    }
}