<?php

namespace App\Notifications\RH;

use App\Models\RH\Declaration\DeclarationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeclarationRequestNotification extends Notification
{
    use Queueable;

    public DeclarationRequest $declarationRequest;
    public string $action;
    public ?string $comment;

    public function __construct(DeclarationRequest $declarationRequest, string $action, ?string $comment = null)
    {
        $this->declarationRequest = $declarationRequest;
        $this->action = $action;
        $this->comment = $comment;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $employee = $this->declarationRequest->employee;

        $messages = [
            'submitted' => 'Pedido de ' . ($this->declarationRequest->declarationType?->name ?? 'declaração') . ' submetido.',
            'approved' => 'Pedido de ' . ($this->declarationRequest->declarationType?->name ?? 'declaração') . ' aprovado.',
            'rejected' => 'Pedido de ' . ($this->declarationRequest->declarationType?->name ?? 'declaração') . ' rejeitado.',
            'issued' => 'Declaração ' . ($this->declarationRequest->issued_number ?? $this->declarationRequest->reference_number) . ' emitida.',
        ];

        return [
            'declaration_request_id' => $this->declarationRequest->id,
            'reference_number' => $this->declarationRequest->reference_number,
            'employee_name' => $employee?->full_name,
            'declaration_type' => $this->declarationRequest->declarationType?->name,
            'status' => $this->declarationRequest->status,
            'comment' => $this->comment,
            'message' => $messages[$this->action] ?? 'Pedido de declaração actualizado.',
            'type' => 'declaration_' . $this->action,
        ];
    }
}
