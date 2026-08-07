<?php

namespace Database\Seeders;

use App\Models\RH\Leave\Holiday;
use App\Services\RH\Leave\HolidayService;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    /**
     * Sincroniza os feriados nacionais de Angola a partir de date.nager.at
     * (anos actual e seguinte, incluindo feriados móveis e pontes).
     * Caso a API esteja indisponível, recorre à lista estática da Lei 20/93.
     */
    public function run(): void
    {
        $service = app(HolidayService::class);
        $synced = false;

        try {
            foreach ([now()->year, now()->year + 1] as $year) {
                $count = $service->syncFromNager($year);
                $this->command?->info("Feriados de {$year} sincronizados via date.nager.at: {$count}.");
            }
            $synced = true;
        } catch (\Exception $e) {
            $this->command?->warn('API de feriados indisponível: '.$e->getMessage());
        }

        if (! $synced) {
            $this->seedStatic();
        }
    }

    /**
     * Feriados nacionais de Angola (Lei 20/93).
     * O ano de referência é fixo (2000) porque os feriados são recorrentes —
     * a correspondência é feita apenas por mês/dia.
     */
    private function seedStatic(): void
    {
        $holidays = [
            ['name' => 'Ano Novo',                                   'date' => '2000-01-01'],
            ['name' => 'Dia da Libertação Nacional',                 'date' => '2000-02-04'],
            ['name' => 'Dia Internacional da Mulher',                'date' => '2000-03-08'],
            ['name' => 'Dia da Paz e da Reconciliação Nacional',     'date' => '2000-04-04'],
            ['name' => 'Dia do Trabalhador',                         'date' => '2000-05-01'],
            ['name' => 'Dia do Herói Nacional',                      'date' => '2000-09-17'],
            ['name' => 'Dia da Independência',                       'date' => '2000-11-11'],
            ['name' => 'Dia de Natal e da Família',                  'date' => '2000-12-25'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::updateOrCreate(
                ['name' => $holiday['name']],
                array_merge($holiday, ['recurrent' => true, 'is_active' => true])
            );
        }

        $this->command?->info('Feriados estáticos (Lei 20/93) semeados como fallback.');
    }
}
