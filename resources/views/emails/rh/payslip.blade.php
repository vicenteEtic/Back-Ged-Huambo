<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Título de Vencimento</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7fb; margin: 0; padding: 0; }
        .wrapper { width: 100%; padding: 30px 0; }
        .container { max-width: 550px; margin: 0 auto; background: #ffffff; border-radius: 14px; padding: 35px 30px; box-shadow: 0 6px 20px rgba(0,0,0,0.10); border-top: 4px solid #27AE60; }
        .logo { text-align: center; margin-bottom: 25px; }
        .logo img { max-width: 160px; }
        h2 { color: #003366; font-size: 22px; margin: 20px 0 5px; text-align: center; font-weight: 700; }
        .subtitle { text-align: center; color: #888; font-size: 13px; margin-bottom: 20px; }
        p { color: #444; font-size: 15px; line-height: 1.6; margin: 10px 0; }
        .highlight { color: #27AE60; font-weight: bold; }
        .meta-box { background: #eaf2f8; padding: 14px 18px; border-radius: 10px; margin: 18px 0; border-left: 4px solid #3498DB; }
        .meta-line { margin: 4px 0; font-size: 14px; }
        .pay-table { width: 100%; border-collapse: collapse; margin: 18px 0; }
        .pay-table th, .pay-table td { padding: 9px 12px; font-size: 14px; text-align: right; border-bottom: 1px solid #eef1f5; }
        .pay-table th { background: #f7fafc; color: #003366; text-align: left; }
        .pay-table td:first-child, .pay-table th:first-child { text-align: left; }
        .pay-table .sub { color: #666; font-size: 13px; }
        .pay-table .total-row td { font-weight: bold; border-top: 2px solid #3498DB; background: #f0f8ff; }
        .pay-table .net-row td { font-weight: bold; font-size: 15px; background: #eafaf1; border-top: 2px solid #27AE60; color: #1e8449; }
        .footer { margin-top: 35px; font-size: 12px; color: #666; text-align: center; border-top: 1px solid #e6e6e6; padding-top: 12px; }
        .money { white-space: nowrap; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">
        <div class="logo">
            <img src="{{ \App\Support\FrontUrl::logo() }}" alt="{{ config('app.name') }}">
        </div>

        <h2>Título de Vencimento</h2>
        <p class="subtitle">Recibo de salário {{ $payslip->period?->name ?? '' }}</p>

        <p>Olá <span class="highlight">{{ $notifiable->first_name ?? $payslip->employee->full_name }}</span>,</p>

        <p>O seu título de vencimento referente ao período acima foi gerado. Consulte os detalhes:</p>

        <div class="meta-box">
            <p class="meta-line"><strong>Funcionário:</strong> {{ $payslip->employee->full_name }}</p>
            <p class="meta-line"><strong>Nº do recibo:</strong> {{ $payslip->payslip_number }}</p>
            <p class="meta-line"><strong>Período:</strong> {{ $payslip->period?->name ?? '---' }}</p>
            <p class="meta-line"><strong>Data de pagamento:</strong> {{ $payslip->payment_date?->format('d/m/Y') ?? '---' }}</p>
        </div>

        <table class="pay-table">
            <tr>
                <th>Descrição</th>
                <th>Valor (Kz)</th>
            </tr>
            <tr>
                <td>Salário Base</td>
                <td class="money">{{ number_format($payslip->base_salary, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Transporte (VT)</td>
                <td class="money">{{ number_format($payslip->transport_allowance, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Alimentação (VA)</td>
                <td class="money">{{ number_format($payslip->meal_allowance, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Horas Extra</td>
                <td class="money">{{ number_format($payslip->overtime, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Outros Rendimentos</td>
                <td class="money">{{ number_format($payslip->other_earnings, 2, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td>Rendimento Bruto</td>
                <td class="money">{{ number_format($payslip->gross_pay, 2, ',', '.') }}</td>
            </tr>
            <tr class="sub">
                <td>INSS</td>
                <td class="money">- {{ number_format($payslip->inss_deduction, 2, ',', '.') }}</td>
            </tr>
            <tr class="sub">
                <td>IRT</td>
                <td class="money">- {{ number_format($payslip->irt_deduction, 2, ',', '.') }}</td>
            </tr>
            <tr class="sub">
                <td>Outros Descontos</td>
                <td class="money">- {{ number_format($payslip->other_deductions, 2, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td>Total de Descontos</td>
                <td class="money">- {{ number_format($payslip->total_deductions, 2, ',', '.') }}</td>
            </tr>
            <tr class="net-row">
                <td>Líquido a Receber</td>
                <td class="money">{{ number_format($payslip->net_pay, 2, ',', '.') }}</td>
            </tr>
        </table>

        <p>Atenciosamente,<br>
            <strong>Departamento de RH — {{ config('app.name') }}</strong></p>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }} — Todos os direitos reservados.</p>
        </div>
    </div>
</div>
</body>
</html>
