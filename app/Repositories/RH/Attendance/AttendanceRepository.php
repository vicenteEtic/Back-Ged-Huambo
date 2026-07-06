<?php

namespace App\Repositories\RH\Attendance;

use App\Models\RH\Attendance\Attendance;
use App\Repositories\AbstractRepository;

class AttendanceRepository extends AbstractRepository
{
    public function __construct(Attendance $model)
    {
        parent::__construct($model);
    }

    public function store(array $data): Attendance
    {
        return $this->model->updateOrCreate(
            ['employee_id' => $data['employee_id'], 'date' => $data['date']],
            $data
        );
    }
}
