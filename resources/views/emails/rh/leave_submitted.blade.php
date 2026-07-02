<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Novo Pedido de Férias</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7fb; margin: 0; padding: 0; }
        .wrapper { width: 100%; padding: 30px 0; }
        .container { max-width: 550px; margin: 0 auto; background: #ffffff; border-radius: 14px; padding: 35px 30px; box-shadow: 0 6px 20px rgba(0,0,0,0.10); border-top: 4px solid #3498DB; }
        .logo { text-align: center; margin-bottom: 25px; }
        .logo img { max-width: 160px; }
        h2 { color: #003366; font-size: 24px; margin: 20px 0 10px; text-align: center; font-weight: 700; }
        p { color: #444; font-size: 15px; line-height: 1.6; margin: 10px 0; }
        .highlight { color: #3498DB; font-weight: bold; }
        .info-box { background: #eaf2f8; padding: 18px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #3498DB; }
        .info-line { margin: 6px 0; font-size: 15px; }
        .btn { display: inline-block; padding: 12px 28px; background: #3498DB; color: #fff; text-decoration: none; border-radius: 8px; font-weight: bold; margin: 15px 0; }
        .footer { margin-top: 35px; font-size: 12px; color: #666; text-align: center; border-top: 1px solid #e6e6e6; padding-top: 12px; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">
        <div class="logo">
            <img src="{{ config('app.logo_url') }}" alt="{{ config('app.name') }}">
        </div>

        <h2>Novo Pedido de Férias</h2>

        <p>Olá <span class="highlight">{{ $notifiable->first_name }}</span>,</p>

        <p>Foi submetido um novo pedido de férias que requer a sua aprovação:</p>

        <div class="info-box">
            <p class="info-line"><strong>Funcionário:</strong> {{ $leaveRequest->employee->full_name }}</p>
            <p class="info-line"><strong>Tipo:</strong> {{ $leaveRequest->leaveType?->name ?? 'Férias' }}</p>
            <p class="info-line"><strong>Data de início:</strong> {{ $leaveRequest->start_date->format('d/m/Y') }}</p>
            <p class="info-line"><strong>Data de fim:</strong> {{ $leaveRequest->end_date->format('d/m/Y') }}</p>
            <p class="info-line"><strong>Dias úteis:</strong> {{ $leaveRequest->total_days }}</p>
            <p class="info-line"><strong>Motivo:</strong> {{ $leaveRequest->reason ?? '---' }}</p>
        </div>

        <p>Atenciosamente,<br>
            <strong>Departamento de RH — {{ config('app.name') }}</strong></p>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }} — Todos os direitos reservados.</p>
        </div>
    </div>
</div>
</body>
</html>
