<?php

namespace Database\Factories\RH\Disciplinary;

use App\Models\RH\Disciplinary\DisciplinaryRecord;
use App\Models\RH\Disciplinary\DisciplinaryType;
use App\Models\RH\Employee\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DisciplinaryRecordFactory extends Factory
{
    protected $model = DisciplinaryRecord::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'disciplinary_type_id' => DisciplinaryType::factory(),
            'incident_date' => fake()->dateTimeThisYear(),
            'description' => fake()->paragraph(),
            'status' => 'pending',
            'recorded_by' => User::factory(),
        ];
    }
}
