<?php

namespace Database\Seeders;

use Database\Seeders\SolSeguros\AgenteProdutorSeeder;
use Database\Seeders\SolSeguros\CaeSeeder;
use Database\Seeders\SolSeguros\IndicatorSeeder;
use Database\Seeders\SolSeguros\ProdutoSeeder;
use Illuminate\Database\Seeder;

class SolSeguros extends Seeder
{
    public function run(): void
    {
        $this->call([
            IndicatorSeeder::class,
            CaeSeeder::class,
            ProdutoSeeder::class,
            AgenteProdutorSeeder::class
        ]);
    }
}