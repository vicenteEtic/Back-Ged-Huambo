<?php

namespace Tests\Feature\RH\Declaration;

use App\Models\RH\Declaration\DeclarationRequest;
use App\Models\RH\Declaration\DeclarationType;
use App\Models\RH\Department\Department;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Payroll\Payslip;
use App\Models\RH\Position\Position;
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

    public function test_type_fields_returns_specific_fields(): void
    {
        $type = DeclarationType::factory()->create(['code' => 'credito_express']);

        $response = $this->getJsonAuth('/api/rh/declarations/types/'.$type->code.'/fields');

        $response->assertStatus(200)
            ->assertJsonPath('type.code', 'credito_express')
            ->assertJsonMissingPath('common_fields')
            ->assertJsonStructure(['fields' => [
                'paying_entity' => ['label', 'type'],
                'payment_day' => ['label', 'type'],
                'consignment_account' => ['label', 'type'],
            ]]);
    }

    public function test_type_fields_returns_404_for_unknown_type(): void
    {
        $this->getJsonAuth('/api/rh/declarations/types/inexistente/fields')
            ->assertStatus(404);
    }

    public function test_salary_fields_are_excluded_from_form(): void
    {
        $type = DeclarationType::factory()->create(['code' => 'informacao_salarial']);

        $response = $this->getJsonAuth('/api/rh/declarations/types/'.$type->code.'/fields');

        $response->assertStatus(200);

        foreach (['salary_type', 'salary_amount', 'net_salary_amount', 'salary_words', 'net_salary_words'] as $key) {
            $response->assertJsonMissingPath('fields.'.$key);
        }
    }

    public function test_employee_derived_fields_are_excluded_from_form(): void
    {
        $byType = [
            'adiantamento_salario' => ['position_category', 'workplace', 'bank', 'id_card_number'],
            'correccao_nome_sigfe' => ['employment_bond'],
            'informacao_salarial' => ['position'],
            'concurso_publico' => ['issuing_department'],
        ];

        foreach ($byType as $code => $keys) {
            $type = DeclarationType::factory()->create(['code' => $code]);

            $response = $this->getJsonAuth('/api/rh/declarations/types/'.$type->code.'/fields');
            $response->assertStatus(200);

            foreach ($keys as $key) {
                $response->assertJsonMissingPath('fields.'.$key);
            }
        }
    }

    public function test_submit_stores_all_fields_and_generates_salary_extenso(): void
    {
        $type = DeclarationType::factory()->create(['code' => 'credito_express']);

        $response = $this->postJsonAuth('/api/rh/declarations', [
            'employee_id' => $this->employee->id,
            'declaration_type_id' => $type->id,
            'id_card_number' => '002456789012',
            'employer_entity' => 'Governo da Província do Huambo',
            'paying_entity' => 'Governo da Província do Huambo',
            'admission_date' => '2008-02-12',
            'payment_day' => 'até ao dia 30 de cada mês',
            'consignment_account' => 'BFA-123456789',
            'salary_amount' => 1250000.50,
            'position_category' => 'Técnico Superior de 1ª Classe',
            'gender' => 'feminino',
        ]);

        $response->assertStatus(201);

        $declaration = DeclarationRequest::find($response->json('id'));

        $this->assertSame('002456789012', $declaration->id_card_number);
        $this->assertSame('Governo da Província do Huambo', $declaration->employer_entity);
        $this->assertSame('1250000.50', (string) $declaration->salary_amount);
        $this->assertSame(
            'um milhão duzentos e cinquenta mil kwanzas e cinquenta centavos',
            $declaration->salary_words
        );
        $this->assertSame($this->employee->full_name, $declaration->full_name);
        $this->assertSame('feminino', $declaration->gender);
        $this->assertNotNull($declaration->issue_date);

        $content = $declaration->content;
        $this->assertArrayHasKey('statement', $content);
        $this->assertArrayHasKey('issue_date_extenso', $content);
        $this->assertStringContainsString('Senhora', $content['statement']);
        $this->assertSame('1.250.000,50 Kz', $content['fields']['Salary']);
        $this->assertSame(
            'Um milhão duzentos e cinquenta mil kwanzas e cinquenta centavos',
            $content['fields']['Salary (in words)']
        );
    }

    public function test_submit_prefills_employee_data_and_auto_generates_declaration_number(): void
    {
        $type = DeclarationType::factory()->create(['code' => 'informacao_salarial']);

        $employee = Employee::factory()->create([
            'full_name' => 'MARIA FERNANDA DOS SANTOS',
            'gender' => 'feminino',
            'base_salary' => 900000.00,
            'hire_date' => '2015-04-10',
            'bank_name' => 'BAI',
            'bank_iban' => 'AO0600000000000000000000000000001',
            'contract_type' => 'efectivo',
            'document_type' => 'bi',
            'document_number' => '002345678901',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        Payslip::factory()->create([
            'employee_id' => $employee->id,
            'net_pay' => 750000.00,
        ]);

        $response = $this->postJsonAuth('/api/rh/declarations', [
            'employee_id' => $employee->id,
            'declaration_type_id' => $type->id,
        ]);

        $response->assertStatus(201);

        $declaration = DeclarationRequest::find($response->json('id'));

        $this->assertMatchesRegularExpression('/^\d{4}\/GAB-RH\/'.date('Y').'$/', $declaration->declaration_number);
        $this->assertSame('MARIA FERNANDA DOS SANTOS', $declaration->full_name);
        $this->assertSame('feminino', $declaration->gender);
        $this->assertSame('900000.00', (string) $declaration->salary_amount);
        $this->assertSame('novecentos mil kwanzas', $declaration->salary_words);
        $this->assertSame('750000.00', (string) $declaration->net_salary_amount);
        $this->assertSame('BAI', $declaration->bank);
        $this->assertSame('Contrato de Trabalho por Tempo Indeterminado', $declaration->employment_bond);
        $this->assertSame('002345678901', $declaration->id_card_number);
        $this->assertSame($this->position->name, $declaration->position);
        $this->assertNotNull($declaration->position_category);
        $this->assertSame('desde Abril de 2015', $declaration->admission_label);
        $this->assertSame('2015-04-10', $declaration->admission_date);

        $content = $declaration->content;
        $this->assertSame('900.000,00 Kz', $content['fields']['Salary']);
        $this->assertSame('750.000,00 Kz', $content['fields']['Net salary']);
    }

    public function test_submit_validates_enum_fields(): void
    {
        $type = DeclarationType::factory()->create(['code' => 'cartao_debito']);

        $this->postJsonAuth('/api/rh/declarations', [
            'employee_id' => $this->employee->id,
            'declaration_type_id' => $type->id,
            'gender' => 'outro',
            'salary_type' => 'inválido',
        ])->assertStatus(422);
    }

    public function test_download_docx_for_issued_declaration(): void
    {
        $type = DeclarationType::factory()->create(['code' => 'informacao_salarial']);

        $declaration = DeclarationRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'declaration_type_id' => $type->id,
            'status' => 'issued',
            'declaration_number' => 'N.º 45/026',
            'issue_date' => '2026-03-30',
            'signer_role' => 'O DIRECTOR',
            'signer_name' => 'Carlos Tchipindo',
            'gender' => 'masculino',
            'content' => [
                'title' => 'Declaração de Informação Salarial',
                'statement' => 'Declara-se que o Senhor JOÃO MANUEL aufere...',
                'fields' => ['Salário base' => '1.250.000,00 Kz'],
            ],
        ]);

        $response = $this->getJsonAuth('/api/rh/declarations/'.$declaration->id.'/download');

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

        $this->getJsonAuth('/api/rh/declarations/'.$declaration->id.'/download')
            ->assertStatus(422);
    }
}
