<?php

namespace Tests\Feature\RH\EmployeeDocument;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\EmployeeDocument\DocumentType;
use App\Models\RH\EmployeeDocument\EmployeeDocument;
use App\Models\RH\Employee\Employee;
use Illuminate\Http\UploadedFile;

class EmployeeDocumentTest extends RhTestCase
{
    protected string $model = EmployeeDocument::class;

    public function test_can_list(): void
    {
        $employee = Employee::factory()->create();
        $response = $this->getJsonAuth(route('employee_document.index', ['employee_id' => $employee->id]));
        $response->assertStatus(200);
    }

    public function test_can_create(): void
    {
        $employee = Employee::factory()->create();
        $data = $this->model::factory()->make(['employee_id' => $employee->id])->toArray();
        $data['file_path'] = [UploadedFile::fake()->create('documento.pdf')];
        $response = $this->postJsonAuth(route('employee_document.store', ['employee_id' => $employee->id]), $data);
        $response->assertStatus(201);
    }

    public function test_can_show(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->getJsonAuth(route('employee_document.show', ['employee_id' => $item->employee_id, 'id' => $item->id]));
        $response->assertStatus(200);
    }

    public function test_can_update(): void
    {
        $item = $this->model::factory()->create();
        $data = $this->model::factory()->make()->toArray();
        unset($data['file_path']);
        $response = $this->putJsonAuth(route('employee_document.update', ['employee_id' => $item->employee_id, 'id' => $item->id]), $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->deleteJsonAuth(route('employee_document.destroy', ['employee_id' => $item->employee_id, 'id' => $item->id]));
        $response->assertStatus(204);
    }

    public function test_bi_requires_issue_date_and_expiry_date(): void
    {
        $employee = Employee::factory()->create();
        $type = DocumentType::factory()->withValidity()->create(['code' => 'BI', 'name' => 'Bilhete de Identidade']);

        $response = $this->postJsonAuth(route('employee_document.store', ['employee_id' => $employee->id]), [
            'employee_id' => $employee->id,
            'document_type_id' => $type->id,
            'name' => 'Bilhete de Identidade',
            'file_path' => [UploadedFile::fake()->create('bi.pdf')],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['issue_date', 'expiry_date']);
    }

    public function test_bi_can_be_registered_with_issue_date_and_expiry_date(): void
    {
        $employee = Employee::factory()->create();
        $type = DocumentType::factory()->withValidity()->create(['code' => 'BI', 'name' => 'Bilhete de Identidade']);

        $response = $this->postJsonAuth(route('employee_document.store', ['employee_id' => $employee->id]), [
            'employee_id' => $employee->id,
            'document_type_id' => $type->id,
            'issue_date' => '2022-03-10',
            'expiry_date' => '2032-03-10',
            'place_of_issue' => 'Huambo',
            'file_path' => [UploadedFile::fake()->create('bi.pdf')],
        ]);

        $response->assertStatus(201);

        $document = $this->model::find($response->json('0.id') ?? $response->json('id'));
        $this->assertSame($type->id, $document->document_type_id);
        $this->assertSame('Bilhete de Identidade', $document->document_type);
        $this->assertSame('Bilhete de Identidade', $document->name);
        $this->assertSame('2022-03-10', $document->issue_date?->toDateString());
        $this->assertSame('2032-03-10', $document->expiry_date?->toDateString());
        $this->assertSame('Huambo', $document->place_of_issue);
    }

    public function test_name_is_determined_by_the_system_and_ignores_client_input(): void
    {
        $employee = Employee::factory()->create();
        $type = DocumentType::factory()->create(['code' => 'CART', 'name' => 'Carta de Condução']);

        $response = $this->postJsonAuth(route('employee_document.store', ['employee_id' => $employee->id]), [
            'employee_id' => $employee->id,
            'document_type_id' => $type->id,
            'name' => 'Nome que o técnico escreveu',
            'file_path' => [UploadedFile::fake()->create('carta.pdf')],
        ]);

        $response->assertStatus(201);

        $document = $this->model::find($response->json('0.id') ?? $response->json('id'));
        $this->assertSame('Carta de Condução', $document->name);
    }

    public function test_name_falls_back_to_original_filename_without_type(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->postJsonAuth(route('employee_document.store', ['employee_id' => $employee->id]), [
            'employee_id' => $employee->id,
            'file_path' => [UploadedFile::fake()->create('meu-ficheiro.pdf')],
        ]);

        $response->assertStatus(201);

        $document = $this->model::find($response->json('0.id') ?? $response->json('id'));
        $this->assertSame('meu-ficheiro.pdf', $document->name);
    }
}
