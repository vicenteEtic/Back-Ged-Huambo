<?php

namespace App\Repositories\RH\Attendance;

use App\Models\RH\Attendance\AttendanceImportLog;
use App\Repositories\AbstractRepository;

class AttendanceImportLogRepository extends AbstractRepository
{
    public function __construct(AttendanceImportLog $model)
    {
        parent::__construct($model);
    }
}
