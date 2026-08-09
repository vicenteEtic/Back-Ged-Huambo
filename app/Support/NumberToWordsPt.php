<?php

namespace App\Support;

/**
 * Conversor de números por extenso em português (PT).
 */
class NumberToWordsPt
{
    private const UNITS = [
        0 => 'zero', 1 => 'um', 2 => 'dois', 3 => 'três', 4 => 'quatro',
        5 => 'cinco', 6 => 'seis', 7 => 'sete', 8 => 'oito', 9 => 'nove',
        10 => 'dez', 11 => 'onze', 12 => 'doze', 13 => 'treze', 14 => 'catorze',
        15 => 'quinze', 16 => 'dezasseis', 17 => 'dezassete', 18 => 'dezoito', 19 => 'dezanove',
    ];

    private const TENS = [
        2 => 'vinte', 3 => 'trinta', 4 => 'quarenta', 5 => 'cinquenta',
        6 => 'sessenta', 7 => 'setenta', 8 => 'oitenta', 9 => 'noventa',
    ];

    private const HUNDREDS = [
        2 => 'duzentos', 3 => 'trezentos', 4 => 'quatrocentos', 5 => 'quinhentos',
        6 => 'seiscentos', 7 => 'setecentos', 8 => 'oitocentos', 9 => 'novecentos',
    ];

    /**
     * Converte um número inteiro (1..999) em palavras.
     */
    private static function threeDigits(int $n): string
    {
        if ($n < 20) {
            return self::UNITS[$n];
        }

        if ($n < 100) {
            $tens = self::TENS[intdiv($n, 10)];
            $unit = $n % 10;

            return $unit === 0 ? $tens : $tens.' e '.self::UNITS[$unit];
        }

        $hundred = intdiv($n, 100);
        $rest = $n % 100;

        if ($hundred === 1) {
            $base = $rest === 0 ? 'cem' : 'cento';
        } else {
            $base = self::HUNDREDS[$hundred];
        }

        if ($rest === 0) {
            return $base;
        }

        return $base.' e '.self::threeDigits($rest);
    }

    /**
     * Converte um grupo (milhares/milhões) em palavras.
     *
     * @param  int  $group  valor do grupo (0..999)
     * @param  int  $scale  1 = unidades, 2 = milhares, 3 = milhões, 4 = mil milhões
     */
    private static function groupToWords(int $group, int $scale): string
    {
        if ($group === 0) {
            return '';
        }

        $words = match ($scale) {
            2 => $group === 1 ? 'mil' : self::threeDigits($group).' mil',
            3 => $group === 1 ? 'um milhão' : self::threeDigits($group).' milhões',
            4 => $group === 1 ? 'mil milhões' : self::threeDigits($group).' mil milhões',
            default => self::threeDigits($group),
        };

        return $words;
    }

    /**
     * Converte um número inteiro em palavras (ex.: 1525 -> "mil quinhentos e vinte e cinco").
     */
    public static function toWords(int|float|string $number): string
    {
        $number = (int) round((float) $number);

        if ($number === 0) {
            return self::UNITS[0];
        }

        $negative = $number < 0;
        $number = abs($number);

        $billions = intdiv($number, 1_000_000_000);
        $millions = intdiv($number % 1_000_000_000, 1_000_000);
        $thousands = intdiv($number % 1_000_000, 1_000);
        $units = $number % 1_000;

        $groups = [];

        if ($billions > 0) {
            $groups[] = ['value' => $billions, 'words' => self::groupToWords($billions, 4)];
        }

        if ($millions > 0) {
            $groups[] = ['value' => $millions, 'words' => self::groupToWords($millions, 3)];
        }

        if ($thousands > 0) {
            $groups[] = ['value' => $thousands, 'words' => self::groupToWords($thousands, 2)];
        }

        if ($units > 0) {
            $groups[] = ['value' => $units, 'words' => self::threeDigits($units)];
        }

        $sentence = '';
        $count = count($groups);

        foreach ($groups as $index => $group) {
            $sentence .= $group['words'];

            if ($index < $count - 1) {
                // "e" antes do grupo seguinte quando este é <= 100 ("mil e um", "mil e cem").
                $nextValue = $groups[$index + 1]['value'];
                $connector = ($nextValue > 0 && $nextValue <= 100) ? ' e ' : ' ';
                $sentence .= $connector;
            }
        }

        return $negative ? 'menos '.$sentence : $sentence;
    }

    /**
     * Converte um valor monetário em palavras (ex.: 1250000.50 -> "um milhão e duzentos e cinquenta mil kwanzas e cinquenta centavos").
     */
    public static function moneyToWords(int|float|string $amount): string
    {
        $amount = (float) $amount;
        $amount = round($amount, 2);

        $kwanza = (int) floor(abs($amount));
        $centavo = (int) round((abs($amount) - $kwanza) * 100);
        $negative = $amount < 0;

        $parts = [];

        if ($kwanza > 0) {
            $parts[] = self::toWords($kwanza).($kwanza === 1 ? ' kwanza' : ' kwanzas');
        }

        if ($centavo > 0) {
            $parts[] = self::toWords($centavo).($centavo === 1 ? ' centavo' : ' centavos');
        }

        $result = implode(' e ', $parts);

        if ($result === '') {
            $result = 'zero kwanzas';
        }

        return $negative ? 'menos '.$result : $result;
    }
}
