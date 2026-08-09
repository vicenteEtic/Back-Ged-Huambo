<?php

namespace Tests\Feature\RH\Declaration;

use App\Models\RH\Declaration\DeclarationRequest;
use App\Models\RH\Declaration\DeclarationType;
use App\Models\RH\Department\Department;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Position\Position;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\RH\RhTestCase;

class DeclarationFieldsTest extends RhTestCase
{
    protected Department $department;
    protected Position $position;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create();
        $this->position = Position::factory()->create(['department_id' => $this->department->id]);
        $this->employee = Employee::factory()->create([
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'user_id' => $this->user->id,
            'gender' => 'feminino',
        ]);
    }

    public function test_type_fields_returns_common_and_specific_fields(): void
    {
        $type = DeclarationType::factory()->create(['code' => 'credito_express']);

        $response = $this->getJsonAuth('/api/rh/declarations/types/' . $type->code . '/fields');

        $response->assertStatus(200)
            ->assertJsonPath('type.code', 'credito_express')
            ->assertJsonPath('common_fields.0', 'nome_completo')
            ->assertJsonStructure(['fields' => [
                'numero_bi' => ['label', 'type'],
                'entidade_empregadora' => ['label', 'type'],
                'data_admissao_completa' => ['label', 'type'],
            ]]);
    }

    public function test_type_fields_returns_404_for_unknown_type(): void
    {
        $this->getJsonAuth('/api/rh/declarations/types/inexistente/fields')
            ->assertStatus(404);
    }

    public function test_submit_stores_all_fields_and_generates_salary_extenso(): void
    {
        $type = DeclarationType::factory()->create(['code' => 'credito_express']);

        $response = $this->postJsonAuth('/api/rh/declarations', [
            'employee_id' => $this->employee->id,
            'declaration_type_id' => $type->id,
            'numero_bi' => '002456789012',
            'entidade_empregadora' => 'Governo da Província do Huambo',
            'entidade_pagadora' => 'Governo da Província do Huambo',
            'data_admissao_completa' => '2008-02-12',
            'dia_pagamento' => 'até ao dia 30 de cada mês',
            'conta_consignacao' => 'BFA-123456789',
            'salario_numero' => 1250000.50,
            'categoria_funcao' => 'Técnico Superior de 1ª Classe',
            'sexo' => 'feminino',
        ]);

        $response->assertStatus(201);

        $declaration = DeclarationRequest::find($response->json('id'));

        $this->assertSame('002456789012', $declaration->numero_bi);
        $this->assertSame('Governo da Província do Huambo', $declaration->entidade_empregadora);
        $this->assertSame('1250000.50', (string) $declaration->salario_numero);
        $this->assertSame(
            'um milhão duzentos e cinquenta mil kwanzas e cinquenta centavos',
            $declaration->salario_extenso
        );
        $this->assertSame($this->employee->full_name, $declaration->nome_completo);
        $this->assertSame('feminino', $declaration->sexo);
        $this->assertNotNull($declaration->data_emissao);

        $content = $declaration->content;
        $this->assertArrayHasKey('statement', $content);
        $this->assertArrayHasKey('data_emissao_extenso', $content);
        $this->assertStringContainsString('Senhora', $content['statement']);
        $this->assertSame('1.250.000,50 Kz', $content['fields']['Salário']);
        $this->assertSame(
            'Um milhão duzentos e cinquenta mil kwanzas e cinquenta centavos',
            $content['fields']['Salário (por extenso)']
        );
    }

    public function test_submit_validates_enum_fields(): void
    {
        $type = DeclarationType::factory()->create(['code' => 'cartao_debito']);

        $this->postJsonAuth('/api/rh/declarations', [
            'employee_id' => $this->employee->id,
            'declaration_type_id' => $type->id,
            'sexo' => 'outro',
            'tipo_salario' => 'inválido',
        ])->assertStatus(422);
    }

    public function test_download_docx_for_issued_declaration(): void
    {
        $type = DeclarationType::factory()->create(['code' => 'informacao_salarial']);

        $declaration = DeclarationRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'declaration_type_id' => $type->id,
            'status' => 'issued',
            'numero_declaracao' => 'N.º 45/026',
            'data_emissao' => '2026-03-30',
            'assinante_cargo' => 'O DIRECTOR',
            'assinante_nome' => 'Carlos Tchipindo',
            'sexo' => 'masculino',
            'content' => [
                'title' => 'Declaração de Informação Salarial',
                'statement' => 'Declara-se que o Senhor JOÃO MANUEL aufere...',
                'fields' => ['Salário base' => '1.250.000,00 Kz'],
            ],
        ]);

        $response = $this->getJsonAuth('/api/rh/declarations/' . $declaration->id . '/download');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $docx = $response->getContent();
        $this->assertStringStartsWith('PK', $docx);
        $this->assertStringContainsString('[Content_Types].xml', substr($docx, 0, 2048));
    }

    public function test_download_docx_rejected_returns_422(): void
    {
        $type = DeclarationType::factory()->create(['code' => 'tutela_menor']);

        $declaration = DeclarationRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'declaration_type_id' => $type->id,
            'status' => 'rejected',
        ]);

        $this->getJsonAuth('/api/rh/declarations/' . $declaration->id . '/download')
            ->assertStatus(422);
    }
}
