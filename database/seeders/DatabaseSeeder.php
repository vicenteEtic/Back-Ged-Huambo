<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        //   User::factory(2)->create();

         $this->call(DiligenceSeeder::class);
         $this->call(IndicatorSeeder::class);
        $this->call(IdentificationCapacitySeeder::class);
        $this->call(ResidenceSeed::class);
         $this->call(EstablishmentSeed::class);
         $this->call(LegalFormSeed::class);
         $this->call(CAESeed::class);
        $this->call(PEPSeed::class);
         $this->call(InsuranceTypeSeed::class);
         $this->call(CountriesSeed::class);
         $this->call(CountriesSeed::class);
         $this->call(TypeActivitySeed::class);
          $this->call(ProfissionPSeed::class);
      
        $this->call(ProfessionNossaSeguros::class);
         $this->call(ProductRiskSeeder::class);
          $this->call(ChannelSeed::class);
         $this->call(PermissionSeed::class);
         $this->call(RiskFormulaSeed::class);
    }
}
