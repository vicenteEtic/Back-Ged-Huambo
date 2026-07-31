<?php

namespace App\Repositories\Process;

use App\Models\Process\ProcessMovement;
use App\Repositories\AbstractRepository;

class ProcessMovementRepository extends AbstractRepository
{
    public function __construct(ProcessMovement $model)
    {
        parent::__construct($model);
    }

    public function byProcess(int $processId)
    {
        return $this->model->where('process_id', $processId)
            ->with(['fromDepartment', 'fromArea', 'toDepartment', 'toArea', 'fromUser', 'toUser'])
            ->orderByDesc('created_at')
            ->get();
    }
}
