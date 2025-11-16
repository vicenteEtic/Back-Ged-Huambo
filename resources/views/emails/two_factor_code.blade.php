<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Código de Autenticação - Nossa Seguros</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #f0f4f9, #d9e4f5); /* degrade suave */
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
            border: 2px solid #0072CE; /* borda azul */
            position: relative;
        }
        .container::before {
            content: "";
            position: absolute;
            top: -6px;
            left: -6px;
            right: -6px;
            bottom: -6px;
            border-radius: 20px;
            background: linear-gradient(135deg, #003366, #0072CE);
            z-index: -1;
        }
        .logo {
            text-align: center;
            margin-bottom: 25px;
        }
        .logo img {
            max-width: 180px;
        }
        h2 {
            color: #003366; /* Azul institucional escuro */
            font-size: 36px;
            margin: 25px 0;
            letter-spacing: 4px;
            text-align: center;
            border: 2px dashed #0072CE; 
            padding: 15px;
            border-radius: 10px;
            background: #f0f8ff;
        }
        p {
            color: #333333;
            font-size: 16px;
            line-height: 1.6;
        }
        .highlight {
            color: #0072CE;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            font-size: 13px;
            color: #666666;
            text-align: center;
            border-top: 1px solid #e5e5e5;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        
     @php
            use Illuminate\Support\Str;
            $currentHost = request()->getHost();
        @endphp

        @if(in_array($currentHost, ['localhost', '127.0.0.1', '172.17.100.11', '172.17.100.12']))
            <div class="logo">
                <img src="https://nossa-denuncias.keepcomply.co.ao:1130/Keepcompay.png" alt="Keepcompay">
            </div>
        @elseif(Str::contains($currentHost, 'nossa-denuncias.keepcomply.co.ao'))
            <div class="logo">
                <img src="https://www.nossaseguros.ao/assets/img/logo.png" alt="Nossa Seguros">
            </div>

              @elseif(Str::contains($currentHost, 'globalseguros.keepcomply.co.ao',[ '172.17.100.14', '172.17.100.12:8083']))
            <div class="logo">
               <img src="https://nossa-denuncias.keepcomply.co.ao:1130/global.png" alt="Keepcompay">
            </div>
        @endif

        <p>Olá,{{ $user->first_name}}</p>
        <p>Seu código de autenticação é:</p>
        
        <h2>{{ $user->two_factor_code }}</h2>

        <p>Este código expira em <span class="highlight">10 minutos</span>.</p>
        <p>Se você não solicitou este código, ignore esta mensagem.</p>

        <div class="footer">
            <p>&copy; {{ date('Y') }}  @if(in_array($currentHost, ['localhost', '127.0.0.1', '172.17.100.11', '172.17.100.12']))
              Keepcompay — Todos os direitos reservados.
        @elseif(Str::contains($currentHost, 'nossa-denuncias.keepcomply.co.ao'))
            Nossa Seguros — Todos os direitos reservados.


               @elseif(Str::contains($currentHost, 'globalseguros.keepcomply.co.ao',[ '172.17.100.14', '172.17.100.14:8083']))
         Global Seguros — Todos os direitos reservados.
        @endif</p>
        </div>
    </div>
</body>
</html>
