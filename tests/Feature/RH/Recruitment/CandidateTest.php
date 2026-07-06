<?php

namespace Tests\Feature\RH\Recruitment;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Recruitment\Candidate;

class CandidateTest extends RhTestCase
{
    protected string $model = Candidate::class;

    public function test_can_list(): void
    {
        $response = $this->getJsonAuth(route('candidate.index'));
        $response->assertStatus(200);
    }

    public function test_can_create(): void
    {
        $data = $this->model::factory()->make()->toArray();
        $response = $this->postJsonAuth(route('candidate.store'), $data);
        $response->assertStatus(201);
    }

    public function test_can_show(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->getJsonAuth(route('candidate.show', $item->id));
        $response->assertStatus(200);
    }

    public function test_can_update(): void
    {
        $item = $this->model::factory()->create();
        $data = $this->model::factory()->make()->toArray();
        $response = $this->putJsonAuth(route('candidate.update', $item->id), $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->deleteJsonAuth(route('candidate.destroy', $item->id));
        $response->assertStatus(204);
    }
}
