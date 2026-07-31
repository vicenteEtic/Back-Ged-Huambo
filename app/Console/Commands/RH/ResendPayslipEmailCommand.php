<?php

namespace App\Console\Commands\RH;

use App\Models\RH\Payroll\Payslip;
use App\Services\RH\Payroll\PayslipService;
use Illuminate\Console\Command;

class ResendPayslipEmailCommand extends Command
{
    protected $signature = 'rh:payslip:email {id}';
    protected $description = 'Reenvia o título de vencimento (recibo PDF) por email';

    public function handle(PayslipService $service): int
    {
        $payslip = Payslip::find((int) $this->argument('id'));

        if (!$payslip) {
            $this->error('Recibo não encontrado.');
            return self::FAILURE;
        }

        $result = $service->sendToEmployee($payslip->id);

        if (!$result['sent']) {
            $this->error("Não foi possível enviar o recibo {$payslip->payslip_number}: o funcionário não tem email associado.");
            return self::FAILURE;
        }

        $this->info("Recibo {$payslip->payslip_number} enviado para {$result['recipient']}.");
        return self::SUCCESS;
    }
}
