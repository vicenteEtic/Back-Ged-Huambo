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

    
     public function index(?int $paginate, ?array $filterParams, ?array $orderByParams, $relationships = [])
    {
        $relationships = [
            'user'
        ];
        $orderByParams = $orderByParams ?? ['created_at' => 'desc'];
        return $this->repository->index($paginate, $filterParams, $orderByParams, $relationships);
    }
}