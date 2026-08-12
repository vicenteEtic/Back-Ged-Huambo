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
        $record = $this->model->withTrashed()
            ->where('employee_id', $data['employee_id'])
            ->where('date', $data['date'])
            ->first();

        if ($record) {
            if ($record->trashed()) {
                $record->restore();
            }
            $record->fill($data)->save();

            return $record;
        }

        return $this->model->create($data);
    }
}
