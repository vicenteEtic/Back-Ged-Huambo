<?php

namespace App\Services\RH\Attendance;

use App\Repositories\RH\Attendance\ShiftRepository;
use App\Services\AbstractService;
use Carbon\Carbon;

class ShiftService extends AbstractService
{
    public function __construct(ShiftRepository $repository)
    {
        parent::__construct($repository);
    }

    public function store(array $data)
    {
        $data = $this->calculateDuration($data);

        return parent::store($data);
    }

    public function update(array $data, int $id)
    {
        $data = $this->calculateDuration($data, $id);

        return parent::update($data, $id);
    }

    protected function calculateDuration(array $data, ?int $id = null): array
    {
        unset($data['duration_hours']);

        $hasTime = isset($data['start_time']) || isset($data['end_time']);

        if (! $hasTime) {
            return $data;
        }

        $current = $id ? $this->repository->findOneBy(['id' => $id]) : null;

        $startTime = $data['start_time'] ?? $current?->start_time;
        $endTime = $data['end_time'] ?? $current?->end_time;

        if (empty($startTime) || empty($endTime)) {
            return $data;
        }

        $start = Carbon::createFromFormat('H:i:s', $startTime);
        $end = Carbon::createFromFormat('H:i:s', $endTime);

        if (! $start || ! $end) {
            return $data;
        }

        $diffMinutes = $start->diffInMinutes($end);

        if ($end->lessThanOrEqualTo($start)) {
            $diffMinutes = (24 * 60) - $diffMinutes;
        }

        $data['duration_hours'] = round($diffMinutes / 60, 2);

        return $data;
    }
}
