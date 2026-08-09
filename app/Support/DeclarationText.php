<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Auxiliares de texto para as declarações: datas por extenso e artigos de género.
 */
class DeclarationText
{
    private const MONTHS = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
    ];

    private const GENDER = [
        'masculino' => [
            'tratamento' => 'Senhor',
            'artigo_o' => 'o',
            'artigo_do' => 'do',
            'funcionario' => 'funcionário',
            'colaborador' => 'colaborador',
            'artigo' => 'o',
        ],
        'feminino' => [
            'tratamento' => 'Senhora',
            'artigo_o' => 'a',
            'artigo_do' => 'da',
            'funcionario' => 'funcionária',
            'colaborador' => 'colaboradora',
            'artigo' => 'a',
        ],
    ];

    /**
     * Data por extenso: "30 de Março de 2026".
     */
    public static function dateLonghand(Carbon|string|null $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        $date = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $date->day.' de '.self::MONTHS[$date->month].' de '.$date->year;
    }

    /**
     * Data por extenso em forma de frase: "aos 30 de Março de 2026".
     */
    public static function dateSentence(Carbon|string|null $date): ?string
    {
        $longhand = self::dateLonghand($date);

        return $longhand === null ? null : 'aos '.$longhand;
    }

    /**
     * Textos derivados do sexo do funcionário.
     */
    public static function gender(?string $sexo): array
    {
        $gender = self::GENDER[$sexo] ?? self::GENDER['masculino'];

        return array_merge($gender, ['sexo' => $sexo]);
    }

    /**
     * "funcionário(a)" conforme o sexo (ex.: "funcionário" / "funcionária").
     */
    public static function funcionario(?string $sexo): string
    {
        return self::gender($sexo)['funcionario'];
    }

    /**
     * Converte um valor monetário em string por extenso.
     */
    public static function moneyToWords($amount): string
    {
        return NumberToWordsPt::moneyToWords($amount);
    }
}
