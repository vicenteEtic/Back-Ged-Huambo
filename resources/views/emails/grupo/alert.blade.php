<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Novo Alerta AML</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #f0f4f9, #d9e4f5);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 520px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 16px;
            padding: 35px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
            border: 2px solid #0072CE;
            position: relative;
        }

        .logo {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo img {
            max-width: 180px;
        }

        .footer {
            margin-top: 30px;
            font-size: 13px;
            color: #666;
            text-align: center;
            border-top: 1px solid #e5e5e5;
            padding-top: 15px;
        }

        .highlight {
            color: #0072CE;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">

        @php
            $type = match($alert->list) {
                'PEP List world' => 'PEP',
                'Sanctions List' => 'Sanctions List',
                'Avaliação AML Reforçada' => 'Enhanced Due Diligence',
                'Avaliação AML Cliente Inaceitável' => 'Unacceptable Customer',
                default => $alert->type,
            };
        @endphp

        {{-- LOGO --}}
        <div class="logo">
            <img src="{{ config('app.logo_url') }}" alt="{{ config('app.name') }}" style="max-width: 180px;">
        </div>

        <p>Olá, <span class="highlight">{{ $user->first_name ?? 'Usuário' }}</span></p>

        <p>Foi identificado um <strong>novo alerta AML</strong> associado à entidade:</p>

        <h3>{{ $alert->entity->social_denomination }}</h3>

        <p><strong>Tipo:</strong> {{ $type }}</p>
        <p><strong>Lista:</strong> {{ $alert->list }}</p>
        <p><strong>Nível:</strong> {{ $alert->level }}</p>
        <p><strong>Score:</strong> {{ $alert->score }}</p>
        <p><strong>Data:</strong> {{ \Carbon\Carbon::parse($alert->created_at)->format('d/m/Y H:i') }}</p>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }} — Todos os direitos reservados.</p>
        </div>

    </div>
</body>

</html>
