<?php
namespace App\Services\KYT;

use App\Repositories\KYT\kytrulesRepository;
use App\Services\AbstractService;

class kytrulesService extends AbstractService
{
    public function __construct(kytrulesRepository $repository)
    {
        parent::__construct($repository);
    }
}