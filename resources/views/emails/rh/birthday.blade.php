<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Feliz Aniversário!</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7fb; margin: 0; padding: 0; }
        .wrapper { width: 100%; padding: 30px 0; }
        .container { max-width: 550px; margin: 0 auto; background: #ffffff; border-radius: 14px; padding: 35px 30px; box-shadow: 0 6px 20px rgba(0,0,0,0.10); border-top: 4px solid #FF6B35; }
        .logo { text-align: center; margin-bottom: 25px; }
        .logo img { max-width: 160px; }
        h2 { color: #003366; font-size: 26px; margin: 20px 0 10px; text-align: center; font-weight: 700; }
        p { color: #444; font-size: 15px; line-height: 1.6; margin: 10px 0; }
        .highlight { color: #FF6B35; font-weight: bold; }
        .info-box { background: #fff4e6; padding: 18px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #FF6B35; }
        .footer { margin-top: 35px; font-size: 12px; color: #666; text-align: center; border-top: 1px solid #e6e6e6; padding-top: 12px; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">
        <div class="logo">
            <img src="{{ config('app.logo_url') }}" alt="{{ config('app.name') }}">
        </div>

        <h2>🎉 Feliz Aniversário!</h2>

        <p>Hoje é um dia especial!</p>

        <div class="info-box">
            <p style="font-size: 18px; text-align: center;">
                <span class="highlight">{{ $employee->full_name }}</span>
            </p>
            <p style="text-align: center; font-size: 14px;">
                {{ $employee->position?->name ?? 'Colaborador' }} —
                {{ $employee->department?->name ?? '' }}
            </p>
        </div>

        <p>
            Em nome de toda a equipa do <strong>{{ config('app.name') }}</strong>,
            desejamos um dia repleto de alegria, saúde e sucesso.
        </p>

        <p>
            Que este novo ano de vida seja marcado por grandes conquistas!
        </p>

        <p style="margin-top: 25px;">
            Com carinho,<br>
            <strong>Equipa {{ config('app.name') }}</strong>
        </p>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }} — Todos os direitos reservados.</p>
        </div>
    </div>
</div>
</body>
</html>
