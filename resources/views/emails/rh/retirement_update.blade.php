<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Actualização do Processo de Reforma</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7fb; margin: 0; padding: 0; }
        .wrapper { width: 100%; padding: 30px 0; }
        .container { max-width: 550px; margin: 0 auto; background: #ffffff; border-radius: 14px; padding: 35px 30px; box-shadow: 0 6px 20px rgba(0,0,0,0.10); border-top: 4px solid #2C3E50; }
        .logo { text-align: center; margin-bottom: 25px; }
        .logo img { max-width: 160px; }
        h2 { color: #003366; font-size: 24px; margin: 20px 0 10px; text-align: center; font-weight: 700; }
        p { color: #444; font-size: 15px; line-height: 1.6; margin: 10px 0; }
        .highlight { color: #2C3E50; font-weight: bold; }
        .info-box { background: #eaeef2; padding: 18px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #2C3E50; }
        .info-line { margin: 6px 0; font-size: 15px; }
        .footer { margin-top: 35px; font-size: 12px; color: #666; text-align: center; border-top: 1px solid #e6e6e6; padding-top: 12px; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">
        <div class="logo">
            <img src="{{ config('app.logo_url') }}" alt="{{ config('app.name') }}">
        </div>

        <h2>Actualização do Processo de Reforma</h2>

        <p>Olá <span class="highlight">{{ $notifiable->first_name }}</span>,</p>

        <p>O processo de reforma de <strong>{{ $process->employee->full_name }}</strong> foi actualizado.</p>

        <div class="info-box">
            <p class="info-line"><strong>Estado actual:</strong> {{ $process->status }}</p>
            <p class="info-line"><strong>Data de solicitação:</strong> {{ $process->request_date->format('d/m/Y') }}</p>
            @if($process->effective_date)
                <p class="info-line"><strong>Data de efeito:</strong> {{ $process->effective_date->format('d/m/Y') }}</p>
            @endif
            @if($process->pension_amount > 0)
                <p class="info-line"><strong>Valor da pensão:</strong> {{ number_format($process->pension_amount, 2, ',', ' ') }} Kz</p>
            @endif
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
