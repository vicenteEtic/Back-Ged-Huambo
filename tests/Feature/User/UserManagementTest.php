<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
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

    public function test_can_get_me()
    {
        $response = $this->getJson('/api/user/me', $this->headers);
        $response->assertStatus(200);
    }

    public function test_can_list_users()
    {
        $response = $this->getJson('/api/user', $this->headers);
        $response->assertStatus(200);
    }
}
