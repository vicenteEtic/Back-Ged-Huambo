<?php

namespace App\Repositories\Process;

use App\Models\Process\Process;
use App\Repositories\AbstractRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ProcessRepository extends AbstractRepository
{
    public function __construct(Process $model)
    {
        parent::__construct($model);
    }

    public function inbox(int $userId, int $departmentId)
    {
        return $this->model->query()
            ->whereHas('assignments', function ($q) use ($userId, $departmentId) {
                $q->where(function ($q2) use ($userId, $departmentId) {
                    $q2->whereHas('technicians', fn ($t) => $t->where('user_id', $userId))
                        ->orWhere(function ($q3) use ($departmentId) {
                            $q3->where('visibility', 'public')
                                ->where('department_id', $departmentId);
                        });
                });
            })
            ->with(['currentDepartment', 'currentArea', 'currentHolder', 'assignments'])
            ->orderByDesc('created_at')
            ->paginate(request('paginate', 50));
    }

    public function outbox(int $userId)
    {
        return $this->model->query()
            ->where('created_by', $userId)
            ->with(['currentDepartment', 'currentHolder'])
            ->orderByDesc('created_at')
            ->paginate(request('paginate', 50));
    }

    public function history()
    {
        return $this->model->query()
            ->whereIn('status', ['closed', 'rejected'])
            ->with(['currentDepartment', 'currentArea', 'currentHolder', 'closedBy'])
            ->orderByDesc('closed_at')
            ->paginate(request('paginate', 50));
    }

    /**
     * Gera número de registo: CÓDIGO_DEPT/YEAR/SEQUENCE
     * Usa selectMax para ser infalível (sem depender de lock)
     */
    public function nextSequenceNumber(string $departmentCode): string
    {
        $year = date('Y');
        $prefix = strtoupper($departmentCode) . '/' . $year;

        $result = DB::select(
            "SELECT MAX(CAST(SUBSTRING_INDEX(sequence_number, '/', -1) AS UNSIGNED)) as max_num
             FROM processes
             WHERE sequence_number LIKE ?
             AND deleted_at IS NULL",
            [$prefix . '/%']
        );

        $maxNum = $result[0]->max_num ?? 0;
        $number = $maxNum + 1;

        return $prefix . '/' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Override do store para tratar duplicate key no sequence_number
     * com retry automático (até 5 tentativas)
     */
    public function store(array $data)
    {
        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return DB::transaction(function () use ($data) {
                    return $this->model->create($data);
                });
            } catch (QueryException $e) {
                if ($attempt < $maxAttempts && str_contains($e->getMessage(), 'Duplicate entry')) {
                    // Regenera o sequence_number e tenta novamente
                    $expediente = \App\Models\RH\Department\Department::where('type', 'expediente')->first();
                    if ($expediente) {
                        $data['sequence_number'] = $this->nextSequenceNumber($expediente->code);
                    }
                    continue;
                }
                throw $e;
            }
        }

        throw new \Exception('Não foi possível gerar número de registo único após ' . $maxAttempts . ' tentativas.');
    }
}
