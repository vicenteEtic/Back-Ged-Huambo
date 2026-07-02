<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Documento a Vencer</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7fb; margin: 0; padding: 0; }
        .wrapper { width: 100%; padding: 30px 0; }
        .container { max-width: 550px; margin: 0 auto; background: #ffffff; border-radius: 14px; padding: 35px 30px; box-shadow: 0 6px 20px rgba(0,0,0,0.10); border-top: 4px solid #E74C3C; }
        .logo { text-align: center; margin-bottom: 25px; }
        .logo img { max-width: 160px; }
        h2 { color: #003366; font-size: 26px; margin: 20px 0 10px; text-align: center; font-weight: 700; }
        p { color: #444; font-size: 15px; line-height: 1.6; margin: 10px 0; }
        .highlight { color: #E74C3C; font-weight: bold; }
        .info-box { background: #fdecea; padding: 18px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #E74C3C; }
        .info-line { margin: 6px 0; font-size: 15px; }
        .warning { color: #E74C3C; font-weight: bold; font-size: 16px; }
        .footer { margin-top: 35px; font-size: 12px; color: #666; text-align: center; border-top: 1px solid #e6e6e6; padding-top: 12px; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">
        <div class="logo">
            <img src="{{ config('app.logo_url') }}" alt="{{ config('app.name') }}">
        </div>

        <h2>Documento a Vencer</h2>

        <p>Olá <span class="highlight">{{ $notifiable->first_name }}</span>,</p>

        <p>O seguinte documento está prestes a expirar:</p>

        <div class="info-box">
            <p class="info-line"><strong>Documento:</strong> {{ $document->name }}</p>
            <p class="info-line"><strong>Funcionário:</strong> {{ $document->employee->full_name ?? 'N/A' }}</p>
            <p class="info-line"><strong>Tipo:</strong> {{ $document->document_type }}</p>
            <p class="info-line"><strong>Data de validade:</strong> {{ $document->expiry_date->format('d/m/Y') }}</p>
            <p class="info-line">
                <span class="warning">
                    @if($daysLeft <= 0)
                        VENCIDO
                    @elseif($daysLeft === 1)
                        Expira amanhã
                    @else
                        Expira em {{ $daysLeft }} dias
                    @endif
                </span>
            </p>
        </div>

        <p>
            Por favor, tome as providências necessárias para a renovação deste documento.
        </p>

        <p style="margin-top: 25px;">
            Atenciosamente,<br>
            <strong>Departamento de RH — {{ config('app.name') }}</strong>
        </p>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }} — Todos os direitos reservados.</p>
        </div>
    </div>
</div>
</body>
</html>
