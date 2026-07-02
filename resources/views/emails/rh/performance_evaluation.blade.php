<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Notificação de Avaliação</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7fb; margin: 0; padding: 0; }
        .wrapper { width: 100%; padding: 30px 0; }
        .container { max-width: 550px; margin: 0 auto; background: #ffffff; border-radius: 14px; padding: 35px 30px; box-shadow: 0 6px 20px rgba(0,0,0,0.10); border-top: 4px solid #F39C12; }
        .logo { text-align: center; margin-bottom: 25px; }
        .logo img { max-width: 160px; }
        h2 { color: #003366; font-size: 24px; margin: 20px 0 10px; text-align: center; font-weight: 700; }
        p { color: #444; font-size: 15px; line-height: 1.6; margin: 10px 0; }
        .highlight { color: #F39C12; font-weight: bold; }
        .info-box { background: #fef9e7; padding: 18px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #F39C12; }
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

        <h2>
            @switch($eventType)
                @case('pending') Avaliação de Desempenho Pendente @break
                @case('completed') Avaliação de Desempenho Concluída @break
                @case('feedback') Feedback de Avaliação Disponível @break
                @default Notificação de Avaliação
            @endswitch
        </h2>

        <p>Olá <span class="highlight">{{ $notifiable->first_name }}</span>,</p>

        @if($eventType === 'pending')
            <p>Tem uma avaliação de desempenho pendente para <strong>{{ $evaluation->employee->full_name }}</strong>.</p>
        @elseif($eventType === 'completed')
            <p>A avaliação de desempenho de <strong>{{ $evaluation->employee->full_name }}</strong> foi concluída.</p>
        @elseif($eventType === 'feedback')
            <p>O feedback da avaliação de <strong>{{ $evaluation->employee->full_name }}</strong> já está disponível.</p>
        @endif

        <div class="info-box">
            <p class="info-line"><strong>Funcionário:</strong> {{ $evaluation->employee->full_name }}</p>
            <p class="info-line"><strong>Ciclo:</strong> {{ $evaluation->cycle?->name ?? '---' }}</p>
            @if($evaluation->final_score)
                <p class="info-line"><strong>Classificação:</strong> {{ number_format($evaluation->final_score, 1) }}/100</p>
                <p class="info-line"><strong>Nível:</strong> {{ $evaluation->rating ?? '---' }}</p>
            @endif
        </div>

        @if($eventType === 'pending')
            <p>Por favor, aceda ao sistema para preencher a avaliação dentro do prazo.</p>
        @endif

        <p>Atenciosamente,<br>
            <strong>Departamento de RH — {{ config('app.name') }}</strong></p>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }} — Todos os direitos reservados.</p>
        </div>
    </div>
</div>
</body>
</html>
