<?php

namespace App\Repositories\RH\Attendance;

use App\Models\RH\Attendance\AttendanceRequestType;
use App\Repositories\AbstractRepository;

class AttendanceRequestTypeRepository extends AbstractRepository
{
    protected $model;

    public function __construct(AttendanceRequestType $model)
    {
        $this->model = $model;
    }

    public function firstByCode(string $code)
    {
        return $this->model->query()->where('code', $code)->first();
    }
}
