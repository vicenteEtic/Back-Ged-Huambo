<?php

namespace App\Services\RH\OverdueValue;

use App\Models\RH\Employee\Employee;
use App\Models\RH\OverdueValue\OverdueValue;
use App\Repositories\RH\OverdueValue\OverdueValueRepository;
use App\Services\AbstractService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OverdueValueService extends AbstractService
{
    public function __construct(OverdueValueRepository $repository)
    {
        parent::__construct($repository);
    }

    public function store(array $data): OverdueValue
    {
        $data = $this->normalize($data);

        return DB::transaction(function () use ($data) {
            return $this->repository->store($data)->fresh(['employee', 'recorder']);
        });
    }

    public function update(array $data, int $id): OverdueValue
    {
        $data = $this->normalize($data);

        return DB::transaction(function () use ($data, $id) {
            return $this->repository->update($data, $id)->fresh(['employee', 'recorder']);
        });
    }

    private function normalize(array $data): array
    {
        $data['recorded_by'] ??= auth()->id();
        $data['amount'] = (float) ($data['amount'] ?? 0);
        $data['paid_amount'] = (float) ($data['paid_amount'] ?? 0);

        if ($data['paid_amount'] <= 0) {
            $data['paid_amount'] = 0;
        }

        if (empty($data['status']) || $data['status'] === 'pending') {
            if ($data['paid_amount'] >= $data['amount']) {
                $data['status'] = 'settled';
                $data['settled_date'] ??= Carbon::today()->format('Y-m-d');
            } elseif ($data['paid_amount'] > 0) {
                $data['status'] = 'partially_paid';
            } else {
                $data['status'] = 'pending';
            }
        } elseif ($data['status'] === 'settled' && empty($data['settled_date'])) {
            $data['settled_date'] = Carbon::today()->format('Y-m-d');
        }

        return $data;
    }

    public function summary(?int $employeeId = null, ?string $type = null, ?string $status = null): array
    {
        $query = OverdueValue::query()
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->whereIn('status', ['pending', 'partially_paid']);

        $overdue = $query->get();

        $totals = [
            'receivable' => (float) $this->byType($overdue, 'receivable')->sum('remaining_amount'),
            'payable' => (float) $this->byType($overdue, 'payable')->sum('remaining_amount'),
            'count' => $overdue->count(),
        ];

        $employees = $overdue
            ->groupBy('employee_id')
            ->map(function ($items, $employeeId) {
                $employee = Employee::withTrashed()->find($employeeId);

                return [
                    'employee_id' => $employeeId,
                    'employee_number' => $employee?->employee_number,
                    'full_name' => $employee?->full_name ?? 'Funcionário removido',
                    'receivable_total' => (float) $this->byType($items, 'receivable')->sum('remaining_amount'),
                    'payable_total' => (float) $this->byType($items, 'payable')->sum('remaining_amount'),
                    'total' => (float) $items->sum('remaining_amount'),
                    'count' => $items->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();

        return [
            'totals' => $totals,
            'employees' => $employees,
        ];
    }

    public function employeeSummary(int $employeeId): array
    {
        $items = OverdueValue::where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'partially_paid'])
            ->with(['employee', 'recorder'])
            ->get();

        return [
            'employee_id' => $employeeId,
            'items' => $items,
            'totals' => [
                'receivable' => (float) $this->byType($items, 'receivable')->sum('remaining_amount'),
                'payable' => (float) $this->byType($items, 'payable')->sum('remaining_amount'),
                'count' => $items->count(),
            ],
        ];
    }

    private function byType($items, string $type)
    {
        return $items->filter(fn ($item) => $item->type?->value === $type);
    }
}
