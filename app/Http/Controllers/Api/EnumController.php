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
}
