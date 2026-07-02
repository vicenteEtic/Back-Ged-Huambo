<?php

namespace Database\Factories\RH\Employee;

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
        return [
            'user_id' => User::factory(),
            'employee_number' => 'EMP-' . fake()->unique()->numerify('#####'),
            'full_name' => fake()->name(),
            'date_of_birth' => fake()->date('Y-m-d', '2000-01-01'),
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
            'hire_date' => fake()->date('Y-m-d', '-2 years'),
            'contract_type' => fake()->randomElement(['efectivo', 'prestacao_servicos', 'estagiario']),
            'base_salary' => fake()->randomFloat(2, 100000, 1000000),
            'bank_name' => fake()->randomElement(['BAI', 'BFA', 'BIC', 'BCA']),
            'bank_iban' => 'AO06' . fake()->numerify('########################'),
            'status' => 'active',
            'category' => fake()->randomElement(['Técnico', 'Senior', 'Chefe', 'Director']),
            'career_regime' => fake()->randomElement(['funcao_publica', 'privado']),
        ];
    }
}
