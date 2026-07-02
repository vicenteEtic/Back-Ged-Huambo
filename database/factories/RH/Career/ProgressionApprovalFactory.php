<?php

namespace Database\Factories\RH\Career;

use App\Models\RH\Career\ProgressionApproval;
use App\Models\RH\Career\ProgressionRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProgressionApprovalFactory extends Factory
{
    protected $model = ProgressionApproval::class;

    public function definition(): array
    {
        return [
            'progression_request_id' => ProgressionRequest::factory(),
            'approver_id' => User::factory(),
            'level' => 1,
            'status' => 'pending',
        ];
    }
}
