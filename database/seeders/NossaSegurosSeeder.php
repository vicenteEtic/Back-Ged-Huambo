<?php

namespace Database\Seeders;


use Database\Seeders\nossaSeguros\CAESeed;
use Database\Seeders\nossaSeguros\CategorySeed;
use Database\Seeders\nossaSeguros\ChannelSeed;
use Database\Seeders\nossaSeguros\CountriesSeed;
use Database\Seeders\nossaSeguros\DiligenceSeeder;
use Database\Seeders\nossaSeguros\EstablishmentSeed;
use Database\Seeders\nossaSeguros\IdentificationCapacitySeeder;
use Database\Seeders\nossaSeguros\IndicatorSeeder;
use Database\Seeders\nossaSeguros\InsuranceTypeSeed;
use Database\Seeders\nossaSeguros\LegalFormSeed;
use Database\Seeders\nossaSeguros\PEPSeed;
use Database\Seeders\nossaSeguros\ProductRiskSeeder;
use Database\Seeders\nossaSeguros\ProfessionSeeder;
use Database\Seeders\nossaSeguros\ResidenceSeed;
use Illuminate\Database\Seeder;

class NossaSegurosSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            IndicatorSeeder::class,
            CAESeed::class,
            InsuranceTypeSeed::class,
            
            CategorySeed::class,
            LegalFormSeed::class,
            ChannelSeed::class,
            PEPSeed::class,
            DiligenceSeeder::class,
            ProductRiskSeeder::class,
            EstablishmentSeed::class,
            ProfessionSeeder::class,
            IdentificationCapacitySeeder::class,
            ResidenceSeed::class,
             CountriesSeed::class,
             
        ]);
    }
}
