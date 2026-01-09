<?php
namespace App\Services\Transation\CustomerProfiles;

use App\Repositories\Transation\CustomerProfiles\CustomerProfilesRepository;
use App\Services\AbstractService;

class CustomerProfilesService extends AbstractService
{
    public function __construct(CustomerProfilesRepository $repository)
    {
        parent::__construct($repository);
    }
}