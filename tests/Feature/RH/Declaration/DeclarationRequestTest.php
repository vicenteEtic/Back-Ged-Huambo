<?php

namespace Tests\Feature\RH\Declaration;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Declaration\DeclarationRequest;
use App\Models\RH\Declaration\DeclarationType;
use App\Models\RH\Department\Department;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Position\Position;

class DeclarationRequestTest extends RhTestCase
{
    protected Department $department;
    protected Position $position;
    protected Employee $employee;
    protected DeclarationType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create();
        $this->position = Position::factory()->create(['department_id' => $this->department->id]);
        $this->employee = Employee::factory()->create([
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'user_id' => $this->user->id,
        ]);
        $this->type = DeclarationType::factory()->create(['requires_approval' => true]);
    }

    public function test_can_list_types()
    {
        $response = $this->getJsonAuth('/api/rh/declarations/types');
        $response->assertStatus(200);
    }

    public function test_can_list()
    {
        DeclarationRequest::factory()->count(2)->create([
            'employee_id' => $this->employee->id,
            'declaration_type_id' => $this->type->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/declarations');
        $response->assertStatus(200);
    }

    public function test_can_submit()
    {
        $response = $this->postJsonAuth('/api/rh/declarations', [
            'employee_id' => $this->employee->id,
            'declaration_type_id' => $this->type->id,
            'institution_name' => 'Banco Nacional',
            'institution_type' => 'banco',
            'purpose' => 'Pedido de crédito',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('reference_number', 'DEC-00001')
            ->assertJsonStructure(['content' => ['employee' => ['full_name']]]);
    }

    public function test_can_show()
    {
        $declaration = DeclarationRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'declaration_type_id' => $this->type->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/declarations/' . $declaration->id);
        $response->assertStatus(200);
    }

    public function test_can_update()
    {
        $declaration = DeclarationRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'declaration_type_id' => $this->type->id,
        ]);

        $response = $this->putJsonAuth('/api/rh/declarations/' . $declaration->id, [
            'purpose' => 'Actualizado para banco',
            'institution_name' => 'Banco BAI',
        ]);
        $response->assertStatus(200);
    }

    public function test_can_destroy()
    {
        $declaration = DeclarationRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'declaration_type_id' => $this->type->id,
        ]);

        $response = $this->deleteJsonAuth('/api/rh/declarations/' . $declaration->id);
        $response->assertStatus(204);
    }

    public function test_can_preview()
    {
        $response = $this->getJsonAuth('/api/rh/declarations/preview?' . http_build_query([
            'declaration_type_id' => $this->type->id,
            'employee_id' => $this->employee->id,
        ]));
        $response->assertStatus(200);
    }

    public function test_can_preview_request()
    {
        $declaration = DeclarationRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'declaration_type_id' => $this->type->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/declarations/' . $declaration->id . '/preview');
        $response->assertStatus(200);
    }

    public function test_can_list_pending()
    {
        DeclarationRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'declaration_type_id' => $this->type->id,
            'status' => 'pending',
        ]);

        $response = $this->getJsonAuth('/api/rh/declarations/pending');
        $response->assertStatus(200);
    }

    public function test_can_approve()
    {
        $declaration = DeclarationRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'declaration_type_id' => $this->type->id,
            'status' => 'pending',
        ]);

        $response = $this->postJsonAuth('/api/rh/declarations/' . $declaration->id . '/approve', [
            'comment' => 'Aprovado pelo RH',
        ]);
        $response->assertStatus(200)
            ->assertJsonPath('status', 'approved');
    }

    public function test_can_reject()
    {
        $declaration = DeclarationRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'declaration_type_id' => $this->type->id,
            'status' => 'pending',
        ]);

        $response = $this->postJsonAuth('/api/rh/declarations/' . $declaration->id . '/reject', [
            'reason' => 'Dados em falta',
        ]);
        $response->assertStatus(200)
            ->assertJsonPath('status', 'rejected');
    }

    public function test_can_issue()
    {
        $declaration = DeclarationRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'declaration_type_id' => $this->type->id,
            'status' => 'approved',
        ]);

        $response = $this->postJsonAuth('/api/rh/declarations/' . $declaration->id . '/issue');
        $expectedNumber = strtoupper(substr($this->type->code, 0, 4)) . '-' . $declaration->reference_number;
        $response->assertStatus(200)
            ->assertJsonPath('status', 'issued')
            ->assertJsonPath('issued_number', $expectedNumber);
    }
}
