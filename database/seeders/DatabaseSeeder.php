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

        $this->call(DepartmentSeed::class);
        $this->call(PermissionSeed::class);
        $this->call(PositionSeed::class);
        $this->call(LeaveTypeSeeder::class);
      
    }
}
