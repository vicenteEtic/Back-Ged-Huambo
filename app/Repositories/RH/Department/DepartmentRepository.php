<?php

namespace App\Repositories\RH\Department;

use App\Models\RH\Department\Department;
use App\Repositories\AbstractRepository;

class DepartmentRepository extends AbstractRepository
{
    protected array $defaultRelations = [
        'responsible',
        'responsibleEmployee',
        'parent',
    ];

    public function __construct(Department $model)
    {
        parent::__construct($model);
    }

    public function index(?int $paginate, ?array $filterParams, ?array $orderByParams, $relationships = [])
    {
        $relationships = array_unique(array_merge((array) $relationships, $this->defaultRelations));

        return parent::index($paginate, $filterParams, $orderByParams, $relationships);
    }

    public function show(int|string $id, array $relationships = [])
    {
        $relationships = array_unique(array_merge($relationships, $this->defaultRelations));

        return parent::show($id, $relationships);
    }
}
