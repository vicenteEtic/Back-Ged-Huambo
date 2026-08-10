<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const RENAMES = [
        'nome_completo' => 'full_name',
        'sexo' => 'gender',
        'data_emissao' => 'issue_date',
        'numero_declaracao' => 'declaration_number',
        'assinante_nome' => 'signer_name',
        'assinante_cargo' => 'signer_role',
        'categoria_funcao' => 'position_category',
        'local_servico' => 'workplace',
        'vinculo' => 'employment_bond',
        'banco' => 'bank',
        'tipo_salario' => 'salary_type',
        'salario_numero' => 'salary_amount',
        'salario_extenso' => 'salary_words',
        'salario_numero_liquido' => 'net_salary_amount',
        'salario_extenso_liquido' => 'net_salary_words',
        'tratamento' => 'salutation',
        'cargo' => 'position',
        'tempo_servico' => 'service_time',
        'data_admissao' => 'admission_label',
        'data_admissao_completa' => 'admission_date',
        'numero_conta' => 'account_number',
        'conta_consignacao' => 'consignment_account',
        'numero_bi' => 'id_card_number',
        'numero_agente' => 'agent_number',
        'telefone' => 'phone',
        'morada' => 'address',
        'balcao_domicilio' => 'domicile_branch',
        'finalidade' => 'credit_purpose',
        'entidade_empregadora' => 'employer_entity',
        'entidade_pagadora' => 'paying_entity',
        'dia_pagamento' => 'payment_day',
        'embaixada' => 'embassy',
        'cidade_embaixada' => 'embassy_city',
        'local_residencia' => 'residence',
        'tipo_correccao' => 'correction_type',
        'departamento_emissor' => 'issuing_department',
    ];

    public function up(): void
    {
        Schema::table('declaration_requests', function (Blueprint $table) {
            foreach (self::RENAMES as $from => $to) {
                $table->renameColumn($from, $to);
            }
        });
    }

    public function down(): void
    {
        Schema::table('declaration_requests', function (Blueprint $table) {
            foreach (self::RENAMES as $from => $to) {
                $table->renameColumn($to, $from);
            }
        });
    }
};
