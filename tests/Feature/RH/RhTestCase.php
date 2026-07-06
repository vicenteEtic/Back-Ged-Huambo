<?php

namespace Tests\Feature\RH;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class RhTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $token = $this->user->createToken('test-token')->plainTextToken;

        $this->headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
    }

    protected function getJsonAuth(string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->getJson($uri, $this->headers);
    }

    protected function postJsonAuth(string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson($uri, $data, $this->headers);
    }

    protected function putJsonAuth(string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->putJson($uri, $data, $this->headers);
    }

    protected function deleteJsonAuth(string $uri): \Illuminate\Testing\TestResponse
    {
        return $this->deleteJson($uri, [], $this->headers);
    }
}
