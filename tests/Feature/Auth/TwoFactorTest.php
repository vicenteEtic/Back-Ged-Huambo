<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_factor_challenge_requires_code()
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'test-secret',
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->postJson('/auth/2fa', [
            'code' => 'invalid',
        ]);

        $response->assertStatus(422);
    }
}
