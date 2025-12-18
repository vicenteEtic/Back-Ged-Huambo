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
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
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
    // ⚠️ $host deve ser passado via Job/Mailable
    $currentHost = $host ?? request()->getHost();
@endphp

{{-- LOGO --}}
@if(in_array($currentHost, ['localhost', '127.0.0.1', '172.17.100.11', '172.17.100.12']))
    <div class="logo">
        <img src="https://listapeps.keepcomply.co.ao/Keepcompay.png" alt="Keepcomply">
    </div>
@elseif(str_contains($currentHost, 'nossa-denuncias.keepcomply.co.ao'))
    <div class="logo">
        <img src="https://www.nossaseguros.ao/assets/img/logo.png" alt="Nossa Seguros">
    </div>
@elseif(in_array($currentHost, ['globalseguros.keepcomply.co.ao', '172.17.100.14', '172.17.100.14:8083']))
    <div class="logo">
        <img src="https://listapeps.keepcomply.co.ao/global.png" alt="Global Seguros">
    </div>
@elseif(in_array($currentHost, ['fortaleza.keepcomply.co.ao', '102.219.127.167']))
    <div class="logo">
        <img src="https://listapeps.keepcomply.co.ao/fortaze.png" alt="Fortaleza Seguros">
    </div>
@endif

<p>Olá, <span class="highlight">{{ $user->first_name }}</span></p>

<p>Foi identificado um <strong>novo alerta AML</strong> associado à entidade:</p>

<h3>{{ $alert->entity->social_denomination }}</h3>

<p><strong>Tipo:</strong> {{ $alert->type }}</p>
<p><strong>Nível:</strong> {{ $alert->level }}</p>
<p><strong>Score:</strong> {{ $alert->score }}</p>
<p><strong>Data:</strong> {{ \Carbon\Carbon::parse($alert->created_at)->format('d/m/Y H:i') }}</p>

<div class="footer">
    <p>&copy; {{ date('Y') }}
        @if(in_array($currentHost, ['localhost', '127.0.0.1', '172.17.100.11', '172.17.100.12']))
            Keepcomply
        @elseif(str_contains($currentHost, 'nossa-denuncias.keepcomply.co.ao'))
            Nossa Seguros
        @elseif(str_contains($currentHost, 'globalseguros'))
            Global Seguros
        @elseif(str_contains($currentHost, 'fortaleza'))
            Fortaleza Seguros
        @else
            Sistema
        @endif
        — Todos os direitos reservados.
    </p>
</div>

</div>
</body>
</html>
