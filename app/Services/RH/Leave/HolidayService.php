<?php

namespace App\Services\RH\Leave;

use App\Models\RH\Leave\Holiday;
use App\Repositories\RH\Leave\HolidayRepository;
use App\Services\AbstractService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class HolidayService extends AbstractService
{
    public function __construct(HolidayRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Sincroniza feriados de um país/ano a partir de date.nager.at.
     */
    public function syncFromNager(int $year, string $countryCode = 'AO'): int
    {
        $response = Http::timeout(15)
            ->get("https://date.nager.at/api/v3/PublicHolidays/{$year}/{$countryCode}");

        if ($response->failed()) {
            throw new \RuntimeException(
                "Erro ao consultar feriados de {$year} ({$countryCode}) em date.nager.at: HTTP {$response->status()}."
            );
        }

        $items = $response->json();
        if (! is_array($items)) {
            throw new \RuntimeException("Resposta inválida de date.nager.at para o ano {$year}.");
        }

        $count = 0;
        foreach ($items as $item) {
            $date = $item['date'] ?? null;
            if (! $date) {
                continue;
            }

            Holiday::updateOrCreate(
                ['date' => $date],
                [
                    'name' => $this->portugueseName($item, $date),
                    'recurrent' => false,
                    'is_active' => true,
                ]
            );
            $count++;
        }

        return $count;
    }

    /**
     * Garante o nome do feriado em português: usa o localName da API
     * e traduz o nome inglês quando o localName não vem preenchido.
     */
    private function portugueseName(array $item, string $date): string
    {
        $localName = trim((string) ($item['localName'] ?? ''));

        if ($localName !== '') {
            return $localName;
        }

        return $this->translateName((string) ($item['name'] ?? $date));
    }

    private function translateName(string $englishName): string
    {
        $names = [
            "New Year's Day" => 'Dia de Ano Novo',
            'Liberation Day' => 'Dia da Libertação',
            'Carnival' => 'Carnaval',
            "International Women's Day" => 'Dia Internacional da Mulher',
            'Day of the Liberation of Southern Africa' => 'Dia da Libertação da África Austral',
            'Good Friday' => 'Sexta-Feira Santa',
            'Peace Day' => 'Dia da Paz',
            'Labour Day' => 'Dia do Trabalhador',
            "National Heroes' Day" => 'Dia do Fundador da Nação e do Herói Nacional',
            "All Souls' Day" => 'Dia dos Fiéis Defuntos',
            'Independence Day' => 'Dia da Independência',
            'Christmas Day' => 'Natal',
        ];

        $bridgeSuffix = ' (Ponte)';
        $isBridge = str_ends_with($englishName, $bridgeSuffix);
        $base = $isBridge ? substr($englishName, 0, -strlen($bridgeSuffix)) : $englishName;

        $translated = $names[$base] ?? $englishName;

        return $isBridge ? $translated.$bridgeSuffix : $translated;
    }

    /**
     * Verifica se uma data é feriado nacional (feriados fixos + Sexta-feira Santa).
     */
    public function isHoliday(Carbon $date): bool
    {
        if ($this->isGoodFriday($date)) {
            return true;
        }

        foreach ($this->all() as $holiday) {
            if ($holiday->recurrent) {
                if ($holiday->date->month === $date->month && $holiday->date->day === $date->day) {
                    return true;
                }
            } elseif ($holiday->date->isSameDay($date)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sexta-feira Santa (feriado móvel) = domingo de Páscoa - 2 dias.
     */
    public function isGoodFriday(Carbon $date): bool
    {
        return $date->isSameDay($this->easterSunday($date->year)->subDays(2));
    }

    public function easterSunday(int $year): Carbon
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::createFromDate($year, $month, $day);
    }

    private function all(): \Illuminate\Support\Collection
    {
        return Holiday::where('is_active', true)->get();
    }
}
