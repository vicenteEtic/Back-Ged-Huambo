<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Vencimento {{ $payslip->payslip_number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; color: #222; font-size: 12px; margin: 0; }
        .header { text-align: center; border-bottom: 3px solid #2563eb; padding-bottom: 12px; }
        .header img { max-height: 50px; }
        .title { font-size: 18px; font-weight: bold; color: #1e3a8a; margin: 10px 0 2px; }
        .doc-no { font-size: 12px; color: #2563eb; font-weight: bold; }
        .meta { width: 100%; border-collapse: collapse; margin: 18px 0; }
        .meta td { padding: 6px 8px; }
        .meta-label { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; }
        .meta-value { font-size: 13px; font-weight: bold; color: #111; }
        .section { font-size: 11px; font-weight: bold; color: #1e3a8a; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid #2563eb; padding: 8px 0 4px; margin-top: 14px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.items td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; font-size: 12px; }
        table.items td.amount { text-align: right; font-weight: bold; white-space: nowrap; }
        table.items tr.sub td { font-size: 11px; color: #6b7280; }
        .total-row td { border-top: 2px solid #2563eb; font-weight: bold; background: #eff6ff; }
        .net-box { margin-top: 16px; padding: 10px 12px; background: #ecfdf5; border: 2px solid #10b981; border-radius: 6px; text-align: center; }
        .net-box .label { font-size: 11px; color: #047857; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .net-box .value { font-size: 18px; font-weight: bold; color: #047857; }
        .footer { margin-top: 26px; text-align: center; font-size: 9px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ \App\Support\FrontUrl::logo() }}" alt="{{ config('app.name') }}">
        <div class="title">Recibo de Vencimento</div>
        <div class="doc-no">Nº {{ $payslip->payslip_number }}</div>
    </div>

    <table class="meta">
        <tr>
            <td>
                <div class="meta-label">Funcionário</div>
                <div class="meta-value">{{ $payslip->employee->full_name }}</div>
            </td>
            <td>
                <div class="meta-label">Período</div>
                <div class="meta-value">{{ $payslip->period?->name ?? '---' }}</div>
            </td>
            <td>
                <div class="meta-label">Pagamento</div>
                <div class="meta-value">{{ $payslip->payment_date?->format('d/m/Y') ?? '---' }}</div>
            </td>
        </tr>
    </table>

    <div class="section">Proventos</div>
    <table class="items">
        <tr><td>Salário base</td><td class="amount">{{ \App\Helpers\Helper::moneyKz($payslip->base_salary) }}</td></tr>
        <tr><td>Subsídio de transporte</td><td class="amount">{{ \App\Helpers\Helper::moneyKz($payslip->transport_allowance) }}</td></tr>
        <tr><td>Subsídio de alimentação</td><td class="amount">{{ \App\Helpers\Helper::moneyKz($payslip->meal_allowance) }}</td></tr>
        <tr><td>Horas extraordinárias</td><td class="amount">{{ \App\Helpers\Helper::moneyKz($payslip->overtime) }}</td></tr>
        <tr><td>Outros proventos</td><td class="amount">{{ \App\Helpers\Helper::moneyKz($payslip->other_earnings) }}</td></tr>
        <tr class="total-row"><td>Total Bruto</td><td class="amount">{{ \App\Helpers\Helper::moneyKz($payslip->gross_pay) }}</td></tr>
    </table>

    <div class="section">Descontos</div>
    <table class="items">
        <tr><td>INSS (3%)</td><td class="amount">{{ \App\Helpers\Helper::moneyKz($payslip->inss_deduction) }}</td></tr>
        <tr><td>IRT</td><td class="amount">{{ \App\Helpers\Helper::moneyKz($payslip->irt_deduction) }}</td></tr>
        <tr><td>Outros descontos</td><td class="amount">{{ \App\Helpers\Helper::moneyKz($payslip->other_deductions) }}</td></tr>
        <tr class="total-row"><td>Total Descontos</td><td class="amount">{{ \App\Helpers\Helper::moneyKz($payslip->total_deductions) }}</td></tr>
    </table>

    <div class="net-box">
        <div class="label">Líquido a Receber</div>
        <div class="value">{{ \App\Helpers\Helper::moneyKz($payslip->net_pay) }}</div>
    </div>

    <div class="footer">
        Documento gerado electronicamente · {{ now()->format('d/m/Y') }}
    </div>
</body>
</html>
