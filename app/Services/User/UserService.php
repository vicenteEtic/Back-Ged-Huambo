<?php

namespace App\Services\User;

use App\Mail\TwoFactorCodeMail as MailTwoFactorCodeMail;
use App\Models\User\User;
use Illuminate\Http\Request;
use App\Services\AbstractService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Repositories\User\UserRepository;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use TwoFactorCodeMail;
use function Illuminate\Log\log;

class UserService extends AbstractService
{
    public function __construct(UserRepository $repository)
    {
        parent::__construct($repository);
    }

    public function store(array $data)
    {
        // 1. Gerar uma senha aleatória caso não venha do front
        $password = Str::random(12); // gera senha aleatória de 12 caracteres
        $data['password'] = Hash::make($password);

        // 2. Salvar o usuário no repositório
        $user = $this->repository->store($data);

        try {
            Mail::to($data['email'])->send(
                new \App\Mail\UserCreatedMail($user, $password)
            );
        } catch (\Throwable $th) {
            Log::error('Erro ao enviar email UserCreatedMail', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
                'email' => $data['email'] ?? null,
            ]);
        }

        return $user;
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $email = $request->email;
        $ip = $request->ip();

        // 🔐 Keys de segurança
        $attemptKey = "login_attempts_{$email}_{$ip}";
        $lockKey = "login_lock_{$email}_{$ip}";

        // ⛔ Verifica bloqueio temporário
        if (Cache::has($lockKey)) {
            return response()->json([
                'message' => 'Número máximo de tentativas atingido. Tente novamente mais tarde.'
            ], 423);
        }

        $user = User::where('email', $email)->first();

        // ❌ Falha de autenticação
        if (!$user || !Hash::check($request->password, $user->password)) {

            $attempts = Cache::get($attemptKey, 0);
            $attempts++;

            Cache::put($attemptKey, $attempts, now()->addMinutes(10));

            // 🔴 Bloqueia após 5 tentativas
            if ($attempts >= 5) {
                Cache::put($lockKey, true, now()->addMinutes(15));
            }

            return response()->json([
                'message' => 'Email ou senha incorretos'
            ], 401);
        }

        // 🔁 Sucesso → limpa tentativas
        Cache::forget($attemptKey);
        Cache::forget($lockKey);

        // 🔐 Gera código 2FA
        $user->generateTwoFactorCode();

        // 📧 Envia email
        Mail::to($user->email)->send(new \App\Mail\TwoFactorCodeMail($user));

        $token = $user->createToken("ged")->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Utilizador autenticado com sucesso. Bem-vindo!',
            'token' => $token,
        ]);
    }


    public function logout(Request $request)
    {
        $user = Auth::user();
        $user->currentAccessToken()->delete();
        return true;
    }

    public function me()
    {
        return Auth::user()->load('role', 'role.permissions');
    }



    public function forgotPassword(string $userEmail): void
    {
        $userEmail = mb_strtolower($userEmail);

        $user = User::query()
            ->where('email', $userEmail)
            ->first();

        if ($user->google_id !== null) return;

        Password::sendResetLink(['email' => $userEmail]);
    }

    public function resetPassword(array $data): void
    {
        $status = Password::reset($data, function (User $user, string $password) {
            $user->update(['password' => $password]);
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw new Exception(trans($status));
        }
    }


    public function verify2fa(array $request)
    {
        $code = trim((string) ($request['code'] ?? null));
        $email = $request['email'] ?? null;

        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuário não encontrado.'
            ], 404);
        }

        $attemptKey = "2fa_attempts_{$user->id}";
        $lockKey = "2fa_lock_{$user->id}";

        if (Cache::has($lockKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Muitas tentativas inválidas. Tente novamente mais tarde.'
            ], 423);
        }

        // ⏱️ VERIFICAR EXPIRAÇÃO PRIMEIRO
        if (!$user->two_factor_expires_at || $user->two_factor_expires_at->lt(now())) {
            return response()->json([
                'status' => 'error',
                'message' => 'O código expirou, solicite um novo.'
            ], 401);
        }

        // ❌ CÓDIGO ERRADO
        if ($user->two_factor_code !== $code) {

            $attempts = Cache::get($attemptKey, 0) + 1;

            Cache::put($attemptKey, $attempts, now()->addMinutes(10));

            if ($attempts >= 3) {
                Cache::put($lockKey, true, now()->addMinutes(15));
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Código inválido.'
            ], 401);
        }

        Cache::forget($attemptKey);
        Cache::forget($lockKey);

        $user->resetTwoFactorCode();

        $token = $user->createToken("keepComply")->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Autenticação 2FA validada com sucesso.',
            'token' => $token,
        ]);
    }

    public function changePassword(array $data, $id)
    {
        return $this->repository->changePassword($data, $id);
    }
}
