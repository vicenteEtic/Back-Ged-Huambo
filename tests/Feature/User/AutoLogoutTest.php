<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AutoLogoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected array $headers;
    protected string $cacheKey = 'user_last_activity_';

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $token = $this->user->createToken('test-token')->plainTextToken;
        $this->headers = ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'];
        $this->cacheKey .= $this->user->id;
        Cache::forget($this->cacheKey);
    }

    public function test_session_expires_after_timeout_inactivity()
    {
        $this->getJson('/api/enums/attendance-statuses', $this->headers)->assertStatus(200);

        // Simula 20 minutos de inactividade (timeout padrão: 15 min)
        Cache::put($this->cacheKey, now()->subMinutes(20), now()->addMinutes(60));

        $this->getJson('/api/enums/attendance-statuses', $this->headers)
            ->assertStatus(401)
            ->assertJson(['message' => 'Sessão expirada por inatividade.']);

        // Token deve ter sido revogado
        $this->assertSame(0, $this->user->tokens()->count());

        // Reset dos guards em cache (artefacto de testes: o RequestGuard guarda o user
        // resolvido no mesmo processo; em produção cada request é um novo ciclo)
        app('auth')->forgetGuards();

        $this->getJson('/api/enums/attendance-statuses', $this->headers)->assertStatus(401);
    }

    public function test_active_session_is_not_expired()
    {
        $this->getJson('/api/enums/attendance-statuses', $this->headers)->assertStatus(200);

        // Actividade recente (5 min atrás) — abaixo do timeout de 15 min
        Cache::put($this->cacheKey, now()->subMinutes(5), now()->addMinutes(60));

        $this->getJson('/api/enums/attendance-statuses', $this->headers)->assertStatus(200);
    }
}
