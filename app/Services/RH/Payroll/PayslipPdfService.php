<?php

namespace App\Services\RH\Payroll;

use App\Models\RH\Payroll\Payslip;
use Dompdf\Dompdf;
use Dompdf\Options;

class PayslipPdfService
{
    public function fileName(Payslip $payslip): string
    {
        return 'Recibo ' . $payslip->payslip_number . '.pdf';
    }

    public function generate(Payslip $payslip): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);

        $html = view('pdfs.payslip', [
            'payslip' => $payslip->loadMissing('employee', 'period'),
        ])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
