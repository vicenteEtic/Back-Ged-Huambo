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
        // O plano de férias é exclusivamente o plano anual; licenças têm fluxo próprio.
        $filterParams = (array) $filterParams;
        if (! array_is_list($filterParams)) {
            $legacyFilters = [];
            foreach ($filterParams as $field => $filter) {
                if (is_array($filter)) {
                    $legacyFilters[] = ['field' => $field] + $filter;
                }
            }
            $filterParams = $legacyFilters;
        }

        $filterParams[] = [
            'field' => 'leaveType.code',
            'filterType' => 'EQUALS',
            'filterValue' => 'ANNUAL',
        ];

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
        $relationships = array_values(array_filter(
            (array) $relationships,
            static fn ($rel) => $rel !== 'user'
                && ! str_contains($rel, '.user')
                && $rel !== 'leaveRequests'
                && $rel !== 'leave_requests'
        ));

        return array_values(array_unique(array_merge($relationships, $this->defaultRelations)));
    }
}
