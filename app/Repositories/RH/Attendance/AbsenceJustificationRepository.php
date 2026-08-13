<?php

namespace App\Repositories\RH\Attendance;

use App\Models\RH\Attendance\AbsenceJustification;
use App\Repositories\AbstractRepository;

class AbsenceJustificationRepository extends AbstractRepository
{
    public function __construct(AbsenceJustification $model)
    {
        parent::__construct($model);
    }

    public function index(?int $paginate, ?array $filterParams, ?array $orderByParams, $relationships = [])
    {
        $relationships = array_values(array_unique(array_merge(
            ['employee', 'attendance', 'submittedBy', 'reviewedBy'],
            $relationships ?? []
        )));

        return parent::index($paginate, $filterParams, $orderByParams, $relationships);
    }

    public function store(array $data): AbsenceJustification
    {
        return $this->model->create($data);
    }

    public function show(int|string $id, array $relationships = [])
    {
        return $this->model->with(['employee', 'attendance', 'submittedBy', 'reviewedBy'])->findOrFail($id);
    }

    public function update(array $data, int $id): AbsenceJustification
    {
        $model = $this->model->findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }
}
