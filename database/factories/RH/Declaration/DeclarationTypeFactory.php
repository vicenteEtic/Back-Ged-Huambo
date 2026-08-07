<?php

namespace Database\Factories\RH\Declaration;

use App\Enum\DeclarationTypeEnum;
use App\Models\RH\Declaration\DeclarationType;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeclarationTypeFactory extends Factory
{
    protected $model = DeclarationType::class;

    public function definition(): array
    {
        $case = fake()->randomElement(DeclarationTypeEnum::cases());

        return [
            'code' => $case->value,
            'name' => $case->label(),
            'description' => $case->description(),
            'requires_approval' => fake()->boolean(),
            'is_active' => true,
        ];
    }
}
