<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Código de Autenticação - Keepcomply</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            margin: 0;
            padding: 0;
        }
        .wrapper {
            width: 100%;
            padding: 30px 0;
        }
        .container {
            max-width: 550px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 14px;
            padding: 35px 30px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.10);
            border-top: 4px solid #0072CE;
        }
        .logo {
            text-align: center;
            margin-bottom: 25px;
        }
        .logo img {
            max-width: 160px;
        }
        h2 {
            color: #003366;
            font-size: 26px;
            margin: 20px 0 10px;
            text-align: center;
            font-weight: 700;
        }
        p {
            color: #444;
            font-size: 15px;
            line-height: 1.6;
            margin: 10px 0;
        }
        .highlight {
            color: #0072CE;
            font-weight: bold;
        }
        .info-box {
            background: #eef5ff;
            padding: 18px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid #0072CE;
        }
        .info-line {
            margin: 6px 0;
            font-size: 15px;
        }
        .password {
            color: #003d99;
            font-weight: bold;
            font-size: 16px;
        }
        .footer {
            margin-top: 35px;
            font-size: 12px;
            color: #666;
            text-align: center;
            border-top: 1px solid #e6e6e6;
            padding-top: 12px;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">

        @php
            use Illuminate\Support\Str;
            $currentHost = request()->getHost();
        @endphp

        {{-- Logo --}}
        @if(in_array($currentHost, ['localhost','127.0.0.1','172.17.100.11','172.17.100.12']))
            <div class="logo">
                <img src="https://listapeps.keepcomply.co.ao/Keepcompay.png" alt="Keepcomply">
            </div>
        @elseif(Str::contains($currentHost, 'nossa-denuncias.keepcomply.co.ao'))
            <div class="logo">
                <img src="https://www.nossaseguros.ao/assets/img/logo.png" alt="Nossa Seguros">
            </div>
        @elseif(Str::contains($currentHost, ['globalseguros.keepcomply.co.ao','172.17.100.14','172.17.100.12:8083']))
            <div class="logo">
                <img src="https://listapeps.keepcomply.co.ao/global.png" alt="Global Seguros">
            </div>
        @elseif(Str::contains($currentHost, ['fortaleza.keepcomply.co.ao','102.219.127.167']))
            <div class="logo">
                <img src="https://listapeps.keepcomply.co.ao/fortaze.png" alt="Fortaleza Seguros">
            </div>
        @endif

        <h2>Bem-vindo ao Portal do Keepcomply</h2>

          <p>Olá <span class="highlight">{{ $user->first_name }}</span>,</p>
        <p>Sua conta no  Portal do Keepcomply foi criada com sucesso.</p>

        <p>Use os dados abaixo para fazer login:</p>

        <div class="info-box">
            <p class="info-line"><strong>Email:</strong> {{ $user->email }}</p>
            <p class="info-line">
                <strong>Senha temporária:</strong>
                <span class="password">{{ $password }}</span>
            </p>
        </div>

        <p>
            Após acessar pela primeira vez, recomendamos que altere sua senha para garantir a segurança da sua conta.
        </p>

        <p>
            Caso você não tenha solicitado esta conta, ignore esta mensagem ou contacte o suporte.
        </p>

        <p style="margin-top: 25px;">
            Atenciosamente,<br>
            <strong>Equipe de Suporte</strong>
        </p>

        <div class="footer">
            <p>&copy; {{ date('Y') }}
                @if(in_array($currentHost, ['localhost','127.0.0.1','172.17.100.11','172.17.100.12']))
                    Keepcomply — Todos os direitos reservados.
                @elseif(Str::contains($currentHost, 'nossa-denuncias.keepcomply.co.ao'))
                    Nossa Seguros — Todos os direitos reservados.
                @elseif(Str::contains($currentHost, ['globalseguros.keepcomply.co.ao','172.17.100.14']))
                    Global Seguros — Todos os direitos reservados.
                @elseif(Str::contains($currentHost, ['fortaleza.keepcomply.co.ao','102.219.127.167']))
                    Fortaleza Seguros — Todos os direitos reservados.
                @else
                    Sistema — Todos os direitos reservados.
                @endif
            </p>
        </div>

    </div>
</div>
</body>
</html>
