<?php

namespace App\Services\RH\Attendance;

use App\Models\RH\Attendance\AttendanceBookConfig;

/**
 * Configuração gerível dos departamentos/gabinetes que assinam o livro de
 * ponto do RH (lista positiva). Sem configuração persiste a regra legada
 * (config/rh.php → ponto.exempt_*), mantendo-se assim sem hardcode de
 * departamentos no backend.
 */
class AttendanceBookConfigService
{
    public function get(): array
    {
        $row = AttendanceBookConfig::query()->latest('id')->first();

        $ids = $this->normalizeIds($row?->attendance_book_departments);

        return [
            'attendance_book_departments' => $ids,
            'configured' => ! empty($ids),
        ];
    }

    public function updateConfiguration(array $departmentIds): array
    {
        $ids = $this->normalizeIds($departmentIds);

        $row = AttendanceBookConfig::query()->latest('id')->first();

        if ($row) {
            $row->update(['attendance_book_departments' => $ids]);
        } else {
            $row = AttendanceBookConfig::create(['attendance_book_departments' => $ids]);
        }

        return [
            'attendance_book_departments' => $row->attendance_book_departments ?? [],
            'configured' => ! empty($ids),
        ];
    }

    public function departmentIds(): array
    {
        return $this->normalizeIds(
            AttendanceBookConfig::query()->latest('id')->first()?->attendance_book_departments
        );
    }

    public function isConfigured(): bool
    {
        return ! empty($this->departmentIds());
    }

    private function normalizeIds(mixed $value): array
    {
        if (! is_array($value) || empty($value)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $value)));
    }
}