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

        $this->call(KytRulesSeeder::class);
        $this->call(KytRuleSeeder::class);
        User::factory(2)->create();
        $this->call(PermissionSeed::class);
        $this->call(RiskFormulaSeed::class);
    }
}
