<?php

namespace Tests\Feature\RH\Performance;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Performance\EvaluationCriterion;

class EvaluationCriterionTest extends RhTestCase
{
    protected string $model = EvaluationCriterion::class;

    public function test_can_list(): void
    {
        $response = $this->getJsonAuth(route('evaluation_criterion.index'));
        $response->assertStatus(200);
    }

    public function test_can_create(): void
    {
        $data = $this->model::factory()->make()->toArray();
        $response = $this->postJsonAuth(route('evaluation_criterion.store'), $data);
        $response->assertStatus(201);
    }

    public function test_can_show(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->getJsonAuth(route('evaluation_criterion.show', $item->id));
        $response->assertStatus(200);
    }

    public function test_can_update(): void
    {
        $item = $this->model::factory()->create();
        $data = $this->model::factory()->make()->toArray();
        $response = $this->putJsonAuth(route('evaluation_criterion.update', $item->id), $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->deleteJsonAuth(route('evaluation_criterion.destroy', $item->id));
        $response->assertStatus(204);
    }
}
