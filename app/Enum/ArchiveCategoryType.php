<?php

namespace App\Enum;

enum ArchiveCategoryType: string
{
    case ProcessoIndividual = 'processo_individual';
    case Administrativo = 'administrativo';
    case Relatorio = 'relatorio';
    case Avaliacao = 'avaliacao';
    case Despacho = 'despacho';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
