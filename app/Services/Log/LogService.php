<?php

namespace App\Services\Log;

use Illuminate\Http\Request;
use App\Services\AbstractService;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Log\LogRepository;

class LogService extends AbstractService
{
    public function __construct(LogRepository $repository)
    {
        parent::__construct($repository);
    }

    public function index(?int $paginate, ?array $filterParams, ?array $orderByParams, $relationships = [])
    {
        $relationships = [
            'entity',
            'user'
        ];

        $orderByParams = $orderByParams ?? ['created_at' => 'desc'];
        return $this->repository->index($paginate, $filterParams, $orderByParams, $relationships);
    }

    public function storeLog(
        ?string $level,
        ?string $typeAction,
        ?string $type,
        ?string $module,
        ?int $idEntity = null,
        ?int $alert_id = null,
        ?string $customMessage = null
    ): void {
        $request = request();

        $message = $customMessage ?? $this->generateMessage($typeAction, $module, $level);

        $this->repository->store([
            'level'           => $level,
            'type'            => $type,
            'module'          => $module,
            'remote_addr'     => $request->ip(),
            'path_info'       => $request->path(),
            'user_name'       => Auth::check() ? Auth::user()->first_name . " " . Auth::user()->last_name : 'guest',
            'user_id' => Auth::id() ?? null,
            'http_user_agent' => $request->userAgent(),
            'message'         => $message,
            'entity_id'       => $idEntity,
            'alert_id'       => $alert_id,

        ]);
    }

    protected function generateMessage(?string $action, ?string $module, ?string $level): string
    {
        $module = $module ?? 'Unknown';
        $action = $action ?? 'unknown';
        $level = $level ?? 'info';

        return match ($action) {
            'create' => "$module criado com sucesso.",
            'edit'   => "$module editado com sucesso.",
            'view'   => "Visualização de $module.",
            'try'    => $level === 'error'
                ? "Erro ao tentar acessar $module."
                : "Tentativa de ação em $module.",
            'delete' => "$module removido com sucesso.",
            default  => "$module - ação desconhecida.",
        };
    }
}
