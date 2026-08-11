<?php

namespace Tests\Feature\RH\EmployeeDocument;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\EmployeeDocument\DocumentType;

class DocumentTypeTest extends RhTestCase
{
    protected string $model = DocumentType::class;

    public function test_can_list(): void
    {
        $response = $this->getJsonAuth(route('document_type.index'));
        $response->assertStatus(200);
    }

    public function test_can_create(): void
    {
        $data = $this->model::factory()->make()->toArray();
        $response = $this->postJsonAuth(route('document_type.store'), $data);
        $response->assertStatus(201);
    }

    public function test_can_show(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->getJsonAuth(route('document_type.show', $item->id));
        $response->assertStatus(200);
    }

    public function test_can_update(): void
    {
        $item = $this->model::factory()->create();
        $data = $this->model::factory()->make()->toArray();
        $response = $this->putJsonAuth(route('document_type.update', $item->id), $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->deleteJsonAuth(route('document_type.destroy', $item->id));
        $response->assertStatus(204);
    }

    public function test_type_with_validity_exposes_metadata(): void
    {
        $item = $this->model::factory()->withValidity()->create();

        $response = $this->getJsonAuth(route('document_type.show', $item->id));

        $response->assertStatus(200)
            ->assertJsonPath('has_issue_date', true)
            ->assertJsonPath('has_expiry_date', true)
            ->assertJsonPath('has_place_of_issue', true);
    }
}
