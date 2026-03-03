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

    // 🚫 Não tentar novamente (erro de negócio ≠ erro técnico)
    public $tries = 1;
    public $timeout = 3600;

    public function __construct(int $alertID, string $host)
    {
        $this->alertID = $alertID;
        $this->host = $host;
    }

    public function handle(): void
    {
        $alert = Alert::find($this->alertID);

        if (!$alert) {
            Log::warning("Alert não encontrado", ['alert_id' => $this->alertID]);
            return;
        }

        // 🔹 Resolver tipo do alerta
        $type = $this->resolveAlertType($alert);

        if (!$type) {
            Log::info('Alert sem tipo elegível para envio de email', [
                'alert_id' => $alert->id,
                'type'     => $alert->type,
                'list'     => $alert->list,
                'category' => $alert->category,
            ]);
            return;
        }

        Log::info("Processando Alert ID {$alert->id} | Tipo: {$type}");

        $grupoTypes = GrupoType::where('name', $type)->get();

        if ($grupoTypes->isEmpty()) {
            Log::info('Nenhum GrupoType encontrado, job finalizado', [
                'alert_id' => $alert->id,
                'type'     => $type,
            ]);
            return;
        }

        foreach ($grupoTypes as $grupo) {
            $userGrupoAlerts = UserGrupoAlert::where('grup_alert_id', $grupo->grup_alert_id)
                ->with('user')
                ->get();

            foreach ($userGrupoAlerts as $uga) {
                if (!$uga->user) {
                    continue;
                }

                $user = $uga->user;

                // Evitar duplicação
                if (AlertUser::where('alert_id', $alert->id)->where('user_id', $user->id)->exists()) {
                    continue;
                }

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
                }
            }
        }
    }

    /**
     * Resolve o tipo do alerta sem ifs duplicados
     */
    private function resolveAlertType(Alert $alert): ?string
    {
        return match (true) {
            $alert->type === 'PEP'              => 'PEP',
            $alert->type === 'SANCTIONS'        => 'Sanctions List',

            $alert->list === 'PEP List world'   => 'PEP',
            $alert->list === 'Sanctions List'   => 'Sanctions List',
            $alert->list === 'AML'              => 'Enhanced Due Diligence',
            $alert->list === 'Avaliação AML Cliente Inaceitável'
                                                => 'Unacceptable Customer',

            $alert->category === 'KYT'          => 'Transaction Monitoring',

            default                             => null,
        };
    }
}
