<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Férias — Preparação Antecipada</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7fb; margin: 0; padding: 0; }
        .wrapper { width: 100%; padding: 30px 0; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 14px; padding: 35px 30px; box-shadow: 0 6px 20px rgba(0,0,0,0.10); border-top: 4px solid #3498DB; }
        .logo { text-align: center; margin-bottom: 25px; }
        .logo img { max-width: 160px; }
        h2 { color: #003366; font-size: 24px; margin: 20px 0 10px; text-align: center; font-weight: 700; }
        p { color: #444; font-size: 15px; line-height: 1.6; margin: 10px 0; }
        .highlight { color: #3498DB; font-weight: bold; }
        .info-box { background: #eaf2f8; padding: 18px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #3498DB; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th { background: #003366; color: #fff; padding: 8px 10px; text-align: left; font-size: 13px; }
        td { border: 1px solid #e6e6e6; padding: 8px 10px; font-size: 14px; color: #444; }
        tr:nth-child(even) td { background: #f7fafd; }
        .tasks { background: #fff8e1; border-left: 4px solid #f39c12; padding: 16px; border-radius: 10px; margin: 20px 0; }
        .tasks li { margin: 6px 0; font-size: 14px; color: #6b5500; }
        .footer { margin-top: 35px; font-size: 12px; color: #666; text-align: center; border-top: 1px solid #e6e6e6; padding-top: 12px; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">
        <div class="logo">
            <img src="{{ \App\Support\FrontUrl::logo() }}" alt="{{ config('app.name') }}">
        </div>

        <h2>Férias — Preparação Antecipada</h2>

        <p>Olá <span class="highlight">{{ $notifiable->first_name }}</span>,</p>

        <p>Informa-se que os seguintes funcionários entrarão de férias no mês de
            <span class="highlight">{{ $monthLabel }} de {{ $year }}</span>.</p>

        <div class="info-box">
            @forelse ($employees as $employee)
                <p class="info-line">
                    <strong>{{ $employee['name'] }}</strong>
                    ({{ $employee['department'] ?? '—' }}) —
                    {{ $employee['days_entitled'] ?? '—' }} dias
                </p>
            @empty
                <p>Nenhum funcionário com férias previstas para este mês.</p>
            @endforelse
        </div>

        <p>Para uma gestão atempada, devem ser preparados os seguintes procedimentos:</p>

        <div class="tasks">
            <ul>
                <li><strong>Guia de férias</strong> — emissão e entrega atempada ao funcionário;</li>
                <li><strong>Processamento do subsídio de férias</strong> — inclusão na folha de salários;</li>
                <li><strong>Outros procedimentos administrativos</strong> — substituições, delegações de tarefas e validações de ponto.</li>
            </ul>
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
