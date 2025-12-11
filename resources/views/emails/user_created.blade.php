<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Conta Criada com Sucesso</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f6f8; margin: 0; padding: 0;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 20px;">
        <tr>
            <td>
                <h2 style="color: #333333;">Olá {{ $name }},</h2>
                <p style="color: #555555; line-height: 1.6;">
                    Sua conta no nosso portal foi criada com sucesso!
                </p>

                <p style="color: #555555; line-height: 1.6;">Para acessar sua conta, utilize os seguintes dados temporários:</p>

                <div style="background-color: #f0f4ff; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                    <p style="margin: 5px 0;"><strong>Email:</strong> {{ $user->email }}</p>
                    <p style="margin: 5px 0;"><strong>Senha temporária:</strong> <span style="color: #1a73e8; font-weight: bold;">{{ $password }}</span></p>
                </div>

                <p style="color: #555555; line-height: 1.6;">
                    Após o primeiro login, recomendamos que altere imediatamente sua senha para manter sua conta segura.
                </p>

                <p style="color: #555555; line-height: 1.6;">
                    Se você não solicitou a criação desta conta, por favor, ignore este e-mail ou entre em contato com nossa equipe de suporte.
                </p>

                <p style="color: #555555; line-height: 1.6; margin-top: 30px;">
                    Atenciosamente,<br>
                    <strong>Equipe de Suporte</strong>
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
