<?php

namespace App\Jobs;

use App\Mail\GrupoAlertMail;
use App\Models\Alert\Alert;
use App\Models\Alert\AlertUser\AlertUser;
use App\Models\Alert\GrupoType\GrupoType;
use App\Models\Alert\UserGrupoAlert\UserGrupoAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendGrupoAlertEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $alertID;
    protected string $host;

    public function __construct(int $alertID, string $host)
    {
        $this->alertID = $alertID;
        $this->host = $host;
    }

    public $tries = 5;
    public $timeout = 36000;

    public function handle(): void
    {
        try {
            $alert = Alert::find($this->alertID);
    
            if (!$alert) {
                Log::error("Alert com ID {$this->alertID} não encontrado.");
                return;
            }
    
            Log::info("Processando Alert ID {$alert->id} | Tipo: {$alert->type}");
    
            $grupoTypes = GrupoType::where('name', $alert->type)->get();
    
            if ($grupoTypes->isEmpty()) {
                Log::warning("Nenhum GrupoType encontrado para alert tipo '{$alert->type}'");
                return;
            }
    
            foreach ($grupoTypes as $grupo) {
                Log::info("Processando GrupoType ID {$grupo->id} | Name: {$grupo->name}");
    
                $userGrupoAlerts = UserGrupoAlert::where('grup_alert_id', $grupo->grup_alert_id)
                    ->with('user')
                    ->get();
    
                foreach ($userGrupoAlerts as $uga) {
                    if (!$uga->user) {
                        Log::warning("UserGrupoAlert ID {$uga->id} não possui usuário relacionado.");
                        continue;
                    }
    
                    $user = $uga->user;
    
                    $alreadyLinked = AlertUser::where('alert_id', $alert->id)
                        ->where('user_id', $user->id)
                        ->exists();
    
                    if ($alreadyLinked) {
                        Log::info("Usuário ID {$user->id} já vinculado ao alert ID {$alert->id}");
                        continue;
                    }
    
                    AlertUser::create([
                        'alert_id' => $alert->id,
                        'user_id'  => $user->id,
                    ]);
    
                    try {
                        Mail::to($user->email)->send(new GrupoAlertMail($user, $alert, $this->host));
                        Log::info("Email enviado para user ID {$user->id} ({$user->email})");
                    } catch (\Throwable $e) {
                        Log::error("Falha ao enviar email para user ID {$user->id}: " . $e->getMessage());
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error("Erro no Job SendGrupoAlertEmailJob: " . $e->getMessage());
        }
    }
    
}

