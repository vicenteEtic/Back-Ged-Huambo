<?php

namespace App\Repositories\RH\Leave;

use App\Models\RH\Leave\LeavePlan;
use App\Repositories\AbstractRepository;

class LeavePlanRepository extends AbstractRepository
{
    protected array $defaultRelations = [
        'employee',
        'employee.department',
        'employee.position',
        'employee.careerCategory',
        'leaveType',
    ];

    public function __construct(LeavePlan $model)
    {
        parent::__construct($model);
    }

    public function index(?int $paginate, ?array $filterParams, ?array $orderByParams, $relationships = [])
    {
        return parent::index($paginate, $filterParams, $orderByParams, $this->withEmployeeRelations($relationships));
    }

    public function show(int|string $id, array $relationships = [])
    {
        return parent::show($id, $this->withEmployeeRelations($relationships));
    }

    /**
     * Garante as relações do funcionário e remove qualquer relação `user`/`*.user`
     * para que as listagens mostrem funcionários, e não utilizadores.
     */
    protected function withEmployeeRelations(array $relationships): array
    {
        $relationships = array_values(array_filter((array) $relationships, static fn ($rel) => $rel !== 'user' && ! str_contains($rel, '.user')));

        return array_values(array_unique(array_merge($relationships, $this->defaultRelations)));
    }
}
