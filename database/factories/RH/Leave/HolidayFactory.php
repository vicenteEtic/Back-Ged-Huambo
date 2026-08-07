<?php

namespace Database\Factories\RH\Leave;

use App\Models\RH\Leave\Holiday;
use Illuminate\Database\Eloquent\Factories\Factory;

class HolidayFactory extends Factory
{
    protected $model = Holiday::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Ano Novo',
                'Dia da Libertação Nacional',
                'Dia Internacional da Mulher',
                'Dia da Paz e da Reconciliação Nacional',
                'Dia do Trabalhador',
                'Dia do Herói Nacional',
                'Dia da Independência',
                'Dia de Natal e da Família',
                'Carnaval',
                'Sexta-Feira Santa',
            ]),
            'date' => fake()->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'recurrent' => false,
            'is_active' => true,
        ];
    }
}
