<?php

namespace Tests\Feature\Log;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $token = $this->user->createToken('test-token')->plainTextToken;
        $this->headers = ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'];
    }

    public function test_can_list_logs()
    {
        $response = $this->getJson('/api/v1/logs', $this->headers);
        $response->assertStatus(200);
    }
}
