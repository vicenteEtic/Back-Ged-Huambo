<?php

namespace App\Services\RH\Payroll;

use App\Models\RH\Payroll\Payslip;
use App\Models\RH\Payroll\PayrollItem;
use App\Notifications\RH\PayslipGeneratedNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class PayslipService
{
    public function generateForPeriod(int $periodId, array $employeeIds = []): int
    {
        $created = [];

        DB::transaction(function () use ($periodId, $employeeIds, &$created) {
            $query = PayrollItem::where('payroll_period_id', $periodId)->where('status', 'approved');
            if (!empty($employeeIds)) {
                $query->whereIn('employee_id', $employeeIds);
            }
            $items = $query->get();

            foreach ($items as $item) {
                $exists = Payslip::where('employee_id', $item->employee_id)
                    ->where('payroll_period_id', $periodId)
                    ->exists();

                if ($exists) continue;

                $period = $item->period;
                $seq = Payslip::where('payroll_period_id', $periodId)->count() + 1;

                $payslip = Payslip::create([
                    'employee_id' => $item->employee_id,
                    'payroll_period_id' => $periodId,
                    'payslip_number' => "P{$period->code}-{$seq}",
                    'base_salary' => $item->base_salary,
                    'transport_allowance' => $item->transport_allowance,
                    'meal_allowance' => $item->meal_allowance,
                    'overtime' => $item->overtime,
                    'other_earnings' => $item->other_earnings,
                    'gross_pay' => $item->gross_pay,
                    'inss_deduction' => $item->inss_deduction,
                    'irt_deduction' => $item->irt_deduction,
                    'other_deductions' => $item->other_deductions,
                    'total_deductions' => $item->total_deductions,
                    'net_pay' => $item->net_pay,
                    'payment_date' => $period->payment_date,
                    'status' => 'generated',
                    'generated_at' => now(),
                ]);

                $created[] = $payslip;
            }
        });

        // 📧 Envia os recibos por email após o commit (falhas de email não cancelam a geração)
        foreach ($created as $payslip) {
            $this->notifyEmployee($payslip);
        }

        return count($created);
    }

    public function notifyEmployee(Payslip $payslip): bool
    {
        try {
            $employee = $payslip->employee;
            $user = $employee?->user;
            $email = $employee?->personal_email ?: $user?->email;

            if ($user) {
                Notification::send($user, (new PayslipGeneratedNotification($payslip))->databaseOnly());
            }

            if ($email) {
                Notification::route('mail', $email)->notify(new PayslipGeneratedNotification($payslip));
                return true;
            }
        } catch (\Throwable $e) {
            Log::error('Erro ao enviar recibo por email', [
                'payslip_id' => $payslip->id,
                'message' => $e->getMessage(),
            ]);
        }

        return false;
    }

    public function sendToEmployee(int $payslipId): array
    {
        $payslip = Payslip::findOrFail($payslipId);

        return [
            'sent' => $this->notifyEmployee($payslip),
            'recipient' => $payslip->employee?->personal_email
                ?? $payslip->employee?->user?->email
                ?? null,
        ];
    }

    public function historyByEmployee(int $employeeId): array
    {
        return Payslip::where('employee_id', $employeeId)
            ->with('period')
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    public function markDownloaded(int $id): Payslip
    {
        $payslip = Payslip::findOrFail($id);
        $payslip->update(['downloaded_at' => now(), 'status' => 'downloaded']);
        return $payslip->fresh();
    }
}
