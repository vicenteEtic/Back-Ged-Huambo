<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Solicitação de Progressão</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7fb; margin: 0; padding: 0; }
        .wrapper { width: 100%; padding: 30px 0; }
        .container { max-width: 550px; margin: 0 auto; background: #ffffff; border-radius: 14px; padding: 35px 30px; box-shadow: 0 6px 20px rgba(0,0,0,0.10); border-top: 4px solid #8E44AD; }
        .logo { text-align: center; margin-bottom: 25px; }
        .logo img { max-width: 160px; }
        h2 { color: #003366; font-size: 24px; margin: 20px 0 10px; text-align: center; font-weight: 700; }
        p { color: #444; font-size: 15px; line-height: 1.6; margin: 10px 0; }
        .highlight { color: #8E44AD; font-weight: bold; }
        .info-box { background: #f0e6f6; padding: 18px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #8E44AD; }
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

        <h2>Nova Solicitação de Progressão</h2>

        <p>Olá <span class="highlight">{{ $notifiable->first_name }}</span>,</p>

        <p>Foi submetida uma nova solicitação de progressão que requer a sua aprovação:</p>

        <div class="info-box">
            <p class="info-line"><strong>Funcionário:</strong> {{ $progression->employee->full_name }}</p>
            <p class="info-line"><strong>Tipo:</strong> {{ $progression->type === 'promotion' ? 'Promoção' : 'Progressão' }}</p>
            <p class="info-line"><strong>Da Categoria:</strong> {{ $progression->from_category ?? '---' }}</p>
            <p class="info-line"><strong>Para Categoria:</strong> {{ $progression->to_category ?? '---' }}</p>
            <p class="info-line"><strong>Salário Actual:</strong> {{ number_format($progression->current_salary, 2, ',', ' ') }} Kz</p>
            <p class="info-line"><strong>Novo Salário:</strong> {{ number_format($progression->new_salary, 2, ',', ' ') }} Kz</p>
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
