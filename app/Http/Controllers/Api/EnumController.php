<?php

namespace App\Http\Controllers\Api;

use App\Enum\ArchiveCategoryType;
use App\Enum\AttendanceStatus;
use App\Enum\BenefitCategory;
use App\Enum\DocumentConfidentiality;
use App\Enum\DocumentSharePermission;
use App\Enum\DocumentStatus;
use App\Enum\FunctionalHistoryType;
use App\Enum\ProgressionType;
use Illuminate\Http\JsonResponse;

class EnumController
{
    public function progressionTypes(): JsonResponse
    {
        return response()->json([
            'data' => collect(ProgressionType::cases())->map(fn($case) => [
                'value' => $case->value,
                'label' => match ($case) {
                    ProgressionType::Progression => 'Progressão',
                    ProgressionType::Promotion => 'Promoção',
                },
            ]),
        ]);
    }

    public function benefitCategories(): JsonResponse
    {
        return response()->json([
            'data' => collect(BenefitCategory::cases())->map(fn($case) => [
                'value' => $case->value,
                'label' => match ($case) {
                    BenefitCategory::Subsidy => 'Subsídio',
                    BenefitCategory::Medical => 'Assistência Médica',
                    BenefitCategory::SocialSupport => 'Apoio Social',
                    BenefitCategory::Institutional => 'Institucional',
                    BenefitCategory::Other => 'Outro',
                },
            ]),
        ]);
    }

    public function documentSharePermissions(): JsonResponse
    {
        return response()->json([
            'data' => collect(DocumentSharePermission::cases())->map(fn($case) => [
                'value' => $case->value,
                'label' => match ($case) {
                    DocumentSharePermission::View => 'Visualizar',
                    DocumentSharePermission::Download => 'Descarregar',
                    DocumentSharePermission::Edit => 'Editar',
                },
            ]),
        ]);
    }

    public function documentStatuses(): JsonResponse
    {
        return response()->json([
            'data' => collect(DocumentStatus::cases())->map(fn($case) => [
                'value' => $case->value,
                'label' => match ($case) {
                    DocumentStatus::Draft => 'Rascunho',
                    DocumentStatus::Published => 'Publicado',
                    DocumentStatus::Archived => 'Arquivado',
                },
            ]),
        ]);
    }

    public function documentConfidentialities(): JsonResponse
    {
        return response()->json([
            'data' => collect(DocumentConfidentiality::cases())->map(fn($case) => [
                'value' => $case->value,
                'label' => match ($case) {
                    DocumentConfidentiality::Public => 'Público',
                    DocumentConfidentiality::Internal => 'Interno',
                    DocumentConfidentiality::Confidential => 'Confidencial',
                    DocumentConfidentiality::Restricted => 'Restrito',
                },
            ]),
        ]);
    }

    public function attendanceStatuses(): JsonResponse
    {
        return response()->json([
            'data' => collect(AttendanceStatus::cases())->map(fn($case) => [
                'value' => $case->value,
                'label' => match ($case) {
                    AttendanceStatus::Present => 'Presente',
                    AttendanceStatus::Absent => 'Ausente',
                    AttendanceStatus::Late => 'Atrasado',
                    AttendanceStatus::JustifiedAbsence => 'Falta Justificada',
                    AttendanceStatus::Holiday => 'Feriado',
                    AttendanceStatus::DayOff => 'Folga',
                },
            ]),
        ]);
    }

    public function archiveCategoryTypes(): JsonResponse
    {
        return response()->json([
            'data' => collect(ArchiveCategoryType::cases())->map(fn($case) => [
                'value' => $case->value,
                'label' => match ($case) {
                    ArchiveCategoryType::ProcessoIndividual => 'Processo Individual',
                    ArchiveCategoryType::Administrativo => 'Administrativo',
                    ArchiveCategoryType::Relatorio => 'Relatório',
                    ArchiveCategoryType::Avaliacao => 'Avaliação',
                    ArchiveCategoryType::Despacho => 'Despacho',
                },
            ]),
        ]);
    }

    public function functionalHistoryTypes(): JsonResponse
    {
        return response()->json([
            'data' => collect(FunctionalHistoryType::cases())->map(fn($case) => [
                'value' => $case->value,
                'label' => match ($case) {
                    FunctionalHistoryType::Appointment => 'Nomeação',
                    FunctionalHistoryType::Promotion => 'Promoção',
                    FunctionalHistoryType::Progression => 'Progressão',
                    FunctionalHistoryType::Transfer => 'Transferência',
                    FunctionalHistoryType::PositionChange => 'Mudança de Cargo',
                    FunctionalHistoryType::SalaryChange => 'Alteração Salarial',
                    FunctionalHistoryType::CategoryChange => 'Mudança de Categoria',
                },
            ]),
        ]);
    }

    // === Process Enums ===

    public function processTypes(): JsonResponse
    {
        return response()->json([
            'data' => [
                ['value' => 'external', 'label' => 'Externo'],
                ['value' => 'internal', 'label' => 'Interno'],
            ],
        ]);
    }

    public function processStatuses(): JsonResponse
    {
        return response()->json([
            'data' => [
                ['value' => 'received', 'label' => 'Recebido'],
                ['value' => 'dispatched_to_chief', 'label' => 'Encaminhado ao Chefe'],
                ['value' => 'dispatched_to_areas', 'label' => 'Distribuído às Áreas'],
                ['value' => 'processing', 'label' => 'Em Tratamento'],
                ['value' => 'pending_validation', 'label' => 'Pendente de Validação'],
                ['value' => 'validated_by_chief', 'label' => 'Validado pelo Chefe'],
                ['value' => 'validated_by_director', 'label' => 'Validado pelo Director'],
                ['value' => 'correction_requested', 'label' => 'Correção Solicitada'],
                ['value' => 'rejected', 'label' => 'Rejeitado'],
                ['value' => 'closed', 'label' => 'Encerrado'],
            ],
        ]);
    }

    public function processClassifications(): JsonResponse
    {
        return response()->json([
            'data' => [
                ['value' => 'pedido', 'label' => 'Pedido'],
                ['value' => 'reclamacao', 'label' => 'Reclamação'],
                ['value' => 'sugestao', 'label' => 'Sugestão'],
                ['value' => 'informacao', 'label' => 'Informação'],
                ['value' => 'outro', 'label' => 'Outro'],
            ],
        ]);
    }

    public function processPriorities(): JsonResponse
    {
        return response()->json([
            'data' => [
                ['value' => 'low', 'label' => 'Baixa'],
                ['value' => 'normal', 'label' => 'Normal'],
                ['value' => 'high', 'label' => 'Alta'],
                ['value' => 'urgent', 'label' => 'Urgente'],
            ],
        ]);
    }

    public function processVisibilities(): JsonResponse
    {
        return response()->json([
            'data' => [
                ['value' => 'public', 'label' => 'Pública'],
                ['value' => 'private', 'label' => 'Privada'],
            ],
        ]);
    }

    public function processAssignmentStatuses(): JsonResponse
    {
        return response()->json([
            'data' => [
                ['value' => 'pending', 'label' => 'Pendente'],
                ['value' => 'processing', 'label' => 'Em Tratamento'],
                ['value' => 'pending_validation', 'label' => 'Pendente de Validação'],
                ['value' => 'validated', 'label' => 'Validado'],
                ['value' => 'completed', 'label' => 'Concluído'],
                ['value' => 'correction_requested', 'label' => 'Correção Solicitada'],
            ],
        ]);
    }

    public function processMovementTypes(): JsonResponse
    {
        return response()->json([
            'data' => [
                ['value' => 'reception', 'label' => 'Recepção'],
                ['value' => 'dispatch_to_chief', 'label' => 'Encaminhamento ao Chefe'],
                ['value' => 'dispatch_to_areas', 'label' => 'Distribuição às Áreas'],
                ['value' => 'add_technician', 'label' => 'Adição de Técnico'],
                ['value' => 'make_public', 'label' => 'Tornar Público'],
                ['value' => 'validation_chief', 'label' => 'Validação do Chefe'],
                ['value' => 'validation_director', 'label' => 'Validação do Director'],
                ['value' => 'correction', 'label' => 'Correção'],
                ['value' => 'rejection', 'label' => 'Rejeição'],
                ['value' => 'closure', 'label' => 'Encerramento'],
            ],
        ]);
    }

    public function departmentTypes(): JsonResponse
    {
        return response()->json([
            'data' => [
                ['value' => 'expediente', 'label' => 'Expediente'],
                ['value' => 'gabinete', 'label' => 'Gabinete'],
                ['value' => 'departamento', 'label' => 'Departamento'],
                ['value' => 'vice_governador', 'label' => 'Vice-Governador'],
            ],
        ]);
    }
}
