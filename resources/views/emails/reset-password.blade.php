@component('mail::message')
<div style="text-align: center; margin-bottom: 20px;">
    <img src="{{ \App\Support\FrontUrl::asset('logo_huambo-D4WV4fyp.png') }}" alt="{{ config('app.name') }}" style="max-width: 160px;">
</div>

# Olá, {{ $notifiable->first_name ?? 'usuário' }}

Recebemos um pedido para redefinir sua senha. Para continuar, clique no botão abaixo:

<a href="{{ $resetUrl }}"
   style="display: inline-block; padding: 10px 20px; background-color: #95C35B; color: white; text-decoration: none; border-radius: 5px;">
   Redefinir Senha
</a>



Se você não solicitou essa alteração, ignore este e-mail.

Atenciosamente,<br>
**Equipe de suporte **
@endcomponent
