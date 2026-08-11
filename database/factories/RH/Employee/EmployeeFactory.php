<?php

namespace Database\Factories\RH\Employee;

use App\Models\RH\Attendance\Shift;
use App\Models\RH\Department\Department;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Position\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        $dateOfBirth = fake()->dateTimeBetween('-55 years', '-19 years');
        $minHire = (clone $dateOfBirth)->modify('+18 years');
        $hireDate = fake()->dateTimeBetween($minHire, '-6 months');

        return [
            'user_id' => User::factory(),
            'employee_number' => 'EMP-' . fake()->unique()->numerify('#####'),
            'full_name' => fake()->name(),
            'date_of_birth' => $dateOfBirth->format('Y-m-d'),
            'gender' => fake()->randomElement(['male', 'female']),
            'marital_status' => fake()->randomElement(['single', 'married', 'divorced']),
            'nationality' => 'Angolana',
            'document_type' => 'bi',
            'document_number' => fake()->unique()->numerify('###########'),
            'nif' => fake()->unique()->numerify('#########'),
            'personal_email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'department_id' => Department::factory(),
            'position_id' => Position::factory(),
            'hire_date' => $hireDate->format('Y-m-d'),
            'effective_date' => fake()->dateTimeBetween($hireDate, 'now')->format('Y-m-d'),
            'contract_type' => fake()->randomElement(['efectivo', 'prestacao_servicos', 'estagiario']),
            'base_salary' => fake()->randomFloat(2, 100000, 1000000),
            'bank_name' => fake()->randomElement(['BAI', 'BFA', 'BIC', 'BCA']),
            'bank_iban' => 'AO06' . fake()->numerify('########################'),
            'status' => 'active',
            'category' => Position::factory(),
            'career_regime' => Shift::factory(),
        ];
    }
}
