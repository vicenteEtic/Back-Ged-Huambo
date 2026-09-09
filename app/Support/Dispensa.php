<?php

namespace App\Support;

use App\Models\RH\Attendance\AttendanceRequest;
use App\Models\RH\Attendance\AttendanceRequestType;

/**
 * Regras centrais de dispensas/solicitações de assiduidade.
 *
 * Centraliza a consulta de dispensas aprovadas e o cálculo do horário
 * reduzido de amamentação (config/rh.php → ponto.dispensa), tal como os
 * PontoExceptions para a excepção de gabinetes do livro de ponto.
 */
class Dispensa
{
    public static function typeRegistry(bool $activeOnly = false): array
    {
        $rows = AttendanceRequestType::query()
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($rows->isNotEmpty()) {
            return $rows->map(static fn (AttendanceRequestType $type) => [
                'id' => $type->id,
                'code' => $type->code,
                'name' => $type->name,
                'description' => $type->description,
                'required_documents' => (array) $type->required_documents,
                'max_days' => $type->max_days,
                'legal_ref' => $type->legal_ref,
                'is_active' => $type->is_active,
                'sort_order' => $type->sort_order,
                'allows_extension' => $type->allows_extension,
                'extension_days' => $type->extension_days,
                'max_extensions' => $type->max_extensions,
            ])->values()->all();
        }

        // Fallback: registry estático em config/rh.php (bases sem seed)
        return config('rh.dispensa.types', []);
    }

    public static function typeByCode(string $code): ?array
    {
        foreach (self::typeRegistry() as $type) {
            if (($type['code'] ?? null) === $code) {
                return $type;
            }
        }

        return null;
    }

    public static function typeById(int $id): ?array
    {
        foreach (self::typeRegistry() as $type) {
            if ((int) ($type['id'] ?? 0) === $id) {
                return $type;
            }
        }

        return null;
    }

    public static function requiredDocuments(string $code): array
    {
        return (array) (self::typeByCode($code)['required_documents'] ?? []);
    }

    public static function documentLabels(): array
    {
        return config('rh.dispensa.document_codes', []);
    }

    public static function statuses(): array
    {
        return config('rh.dispensa.statuses', []);
    }

    /**
     * Verifica se o tipo de solicitação permite prorrogação.
     */
    public static function allowsExtension(string $code): bool
    {
        return (bool) (self::typeByCode($code)['allows_extension'] ?? false);
    }

    /**
     * Dias de cada prorrogação para o tipo informado.
     */
    public static function extensionDays(string $code): ?int
    {
        return self::typeByCode($code)['extension_days'] ?? null;
    }

    /**
     * Número máximo de prorrogações permitidas para o tipo informado.
     */
    public static function maxExtensions(string $code): ?int
    {
        return self::typeByCode($code)['max_extensions'] ?? null;
    }

    /**
     * Verifica se a solicitação pode ser prorrogada (tipo permite e não
     * atingiu o limite de prorrogações).
     */
    public static function canExtend(AttendanceRequest $request, ?int $excludeId = null): bool
    {
        $request->loadMissing('type');

        $code = $request->type?->code;

        if (! $code || ! self::allowsExtension($code)) {
            return false;
        }

        $max = self::maxExtensions($code);

        if ($max === null) {
            return true;
        }

        $count = AttendanceRequest::query()
            ->where('extends_request_id', $request->id)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->count();

        return $count < $max;
    }

    /**
     * A prorrogação só pode ser solicitada enquanto a licença/dispensa
     * estiver vigente, ou seja, dentro do seu intervalo de datas.
     */
    public static function isStillVigent(AttendanceRequest $request): bool
    {
        $today = now()->toDateString();

        return $request->start_date?->toDateString() <= $today
            && $request->end_date?->toDateString() >= $today;
    }

    /**
     * Dispensa aprovada e activa que cobre a data (parcial ou total).
     */
    public static function approvedForDate(int $employeeId, string $date): ?AttendanceRequest
    {
        return self::queryApproved($employeeId, $date)->first();
    }

    /**
     * Dispensa aprovada e activa de dia inteiro que cobre a data.
     */
    public static function approvedFullDayForDate(int $employeeId, string $date): ?AttendanceRequest
    {
        return self::queryApproved($employeeId, $date)
            ->where('applies_full_day', true)
            ->first();
    }

    public static function isBreastfeeding(AttendanceRequest $request): bool
    {
        $request->loadMissing('type');

        return $request->type?->code === 'amamentacao';
    }

    /**
     * Hora prevista de saída com dispensa de amamentação.
     * O benefício desconta o nº de horas previsto em config/rh.php
     * (default: função pública 08:00–15:00 = 7h → 2h de dispensa → 5h de trabalho).
     * Ex.: entrada 08:00 → saída prevista 13:00.
     */
    public static function expectedCheckoutWithBreastfeeding(string $checkIn): ?string
    {
        $start = config('rh.dispensa.work_start', '08:00');
        $end = config('rh.dispensa.work_end', '15:00');
        $reduction = (float) config('rh.dispensa.breastfeeding_reduction_hours', 2);

        $workHours = (strtotime($end) - strtotime($start)) / 3600;
        $dayHours = max(0, $workHours - $reduction);

        return date('H:i:s', strtotime($checkIn) + ($dayHours * 3600));
    }

    /**
     * Fim do benefício de amamentação: 18 meses após o nascimento (agosto de 2026:
     * benefício termina automaticamente aos 18 meses — ver config/rh.php).
     */
    public static function breastfeedingUntil(string $birthDate): string
    {
        $months = (int) config('rh.dispensa.breastfeeding_max_months', 18);

        return \Carbon\Carbon::parse($birthDate)->addMonthsNoOverflow($months)->toDateString();
    }

    private static function queryApproved(int $employeeId, string $date)
    {
        return AttendanceRequest::query()
            ->where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where('benefit_active', true)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date);
    }
}
