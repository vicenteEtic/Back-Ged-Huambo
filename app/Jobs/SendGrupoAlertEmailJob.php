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

    public $tries = 5;      // Tenta até 3 vezes em caso de falha
    public $timeout = 3600; // 1 hora

    public function __construct(int $alertID, string $host)
    {
        $this->alertID = $alertID;
        $this->host = $host;
    }

    public function handle(): void
    {
        try {
            $alert = Alert::find($this->alertID);
            if (!$alert) {
                Log::warning("Alert não encontrado", ['alert_id' => $this->alertID]);
                return;
            }

            $type = $this->resolveAlertType($alert);
            if (!$type) {
                Log::info("Alert sem tipo elegível para envio", ['alert_id' => $alert->id]);
                return;
            }

            Log::info("Processando Alert ID {$alert->id} | Tipo: {$type}");

            $grupoTypes = GrupoType::where('name', $type)->get();
            if ($grupoTypes->isEmpty()) {
                Log::info("Nenhum GrupoType encontrado", ['alert_id' => $alert->id]);
                return;
            }

            foreach ($grupoTypes as $grupo) {
                if (!$grupo->grup_alert_id) {
                    Log::warning("Grupo sem ID", ['grupo' => $grupo->toArray()]);
                    continue;
                }

                $userGrupoAlerts = UserGrupoAlert::where('grup_alert_id', $grupo->grup_alert_id)
                    ->with('user')
                    ->get();

                foreach ($userGrupoAlerts as $uga) {
                    $user = $uga->user;
                    if (!$user || !$user->email) continue;

                    // Evita duplicados
                    AlertUser::firstOrCreate([
                        'alert_id' => $alert->id,
                        'user_id'  => $user->id,
                    ]);

                    try {
                        Mail::to($user->email)
                            ->send(new GrupoAlertMail($user, $alert, $this->host));

                        Log::info("Email enviado", [
                            'alert_id' => $alert->id,
                            'user_id'  => $user->id,
                            'email'    => $user->email,
                        ]);
                    } catch (\Throwable $e) {
                        Log::error("Erro ao enviar email", [
                            'alert_id' => $alert->id,
                            'user_id'  => $user->id,
                            'error'    => $e->getMessage(),
                        ]);
                        // Se quiser re-tentar automaticamente, remova o throw
                        // throw $e;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error("Erro inesperado no job", [
                'alert_id' => $this->alertID,
                'error' => $e->getMessage(),
            ]);
            // Re-lança para que o Laravel registre a tentativa
            throw $e;
        }
    }

    private function resolveAlertType(Alert $alert): ?string
    {
        return match (true) {
            $alert->type === 'PEP'              => 'PEP',
            $alert->type === 'SANCTIONS'        => 'Sanctions List',
            $alert->list === 'PEP List world'   => 'PEP',
            $alert->list === 'Sanctions List'   => 'Sanctions List',
            $alert->list === 'AML'              => 'Enhanced Due Diligence',
            $alert->list === 'Avaliação AML Cliente Inaceitável' => 'Unacceptable Customer',
            $alert->category === 'KYT'          => 'Transaction Monitoring',
            default                             => null,
        };
    }
}
