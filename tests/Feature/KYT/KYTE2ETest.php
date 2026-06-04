<?php

namespace Tests\Feature\KYT;

use Tests\TestCase;
use App\Models\Entities\Entities;
use App\Models\Alert\Alert;
use App\Services\KYT\KYTService;
use App\Enum\TypeEntity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class KYTE2ETest extends TestCase
{
    use RefreshDatabase;

    private KYTService $kytService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kytService = app(KYTService::class);
    }

    public function test_abrupt_capital_increase_individual(): void
    {
        $customer = Entities::create([
            'entity_type' => TypeEntity::SINGULAR->value,
            'customer_number' => 'KYT-E2E-IND-01',
        ]);

        $baseDate = now()->subDays(20);

        $policies = [
            [
                'numero_apolice' => 'POL-REF-001',
                'descricao_produto' => 'SEGURO BAI VIDA',
                'capital' => 1000000.00,
                'premium_total' => 50000.00,
                'data_inicio' => (clone $baseDate)->format('Y-m-d'),
                'estado_apolice' => 'NORMAL',
            ],
            [
                'numero_apolice' => 'POL-NEW-002',
                'descricao_produto' => 'SEGURO BAI VIDA',
                'capital' => 1800000.00,
                'premium_total' => 90000.00,
                'data_inicio' => (clone $baseDate)->addDays(15)->format('Y-m-d'),
                'estado_apolice' => 'NORMAL',
            ],
        ];

        $this->kytService->runAllChecksMemory($customer, $policies);

        $alert = Alert::where('entity_id', $customer->id)
            ->where('type', 'Aumento abrupto de capital entre apólices')
            ->first();

        $this->assertNotNull($alert, 'Alerta de aumento abrupto de capital não foi criado');
        $this->assertEquals('KYT', $alert->category);
    }

    public function test_abrupt_capital_increase_collective(): void
    {
        $customer = Entities::create([
            'entity_type' => TypeEntity::COLECTIVA->value,
            'customer_number' => 'KYT-E2E-COL-01',
        ]);

        $baseDate = now()->subDays(20);

        $policies = [
            [
                'numero_apolice' => 'POL-REF-COL-001',
                'descricao_produto' => 'SEGURO DE POUPANÇA VIDA (SPV) GRUPO FECHADO',
                'capital' => 5000000.00,
                'premium_total' => 250000.00,
                'data_inicio' => (clone $baseDate)->format('Y-m-d'),
                'estado_apolice' => 'NORMAL',
            ],
            [
                'numero_apolice' => 'POL-NEW-COL-002',
                'descricao_produto' => 'SEGURO DE POUPANÇA VIDA (SPV) GRUPO FECHADO',
                'capital' => 9000000.00,
                'premium_total' => 450000.00,
                'data_inicio' => (clone $baseDate)->addDays(15)->format('Y-m-d'),
                'estado_apolice' => 'NORMAL',
            ],
        ];

        $this->kytService->runAllChecksMemory($customer, $policies);

        $alert = Alert::where('entity_id', $customer->id)
            ->where('type', 'Aumento abrupto de capital entre apólices')
            ->first();

        $this->assertNotNull($alert, 'Alerta de aumento abrupto de capital (colectiva) não foi criado');
    }

    public function test_policy_lifecycle_abuse_individual(): void
    {
        $customer = Entities::create([
            'entity_type' => TypeEntity::SINGULAR->value,
            'customer_number' => 'KYT-E2E-LIFE-IND-01',
        ]);

        $baseDate = now()->subDays(30);

        $policies = [];
        $refunds = [];
        $product = 'SEGURO DE POUPANÇA VIDA (SPV) INDIVIDUAL';

        for ($i = 1; $i <= 3; $i++) {
            $polNum = "POL-LIFE-IND-{$i}";
            $start = (clone $baseDate)->addDays($i * 10);
            $end = (clone $start)->addDays(30);
            $policies[] = [
                'numero_apolice' => $polNum,
                'descricao_produto' => $product,
                'capital' => 5000000.00,
                'premium_total' => 500000.00,
                'data_inicio' => $start->format('Y-m-d'),
                'data_fim' => $end->format('Y-m-d'),
                'estado_apolice' => 'CANCELADA',
            ];
            $refunds[] = [
                'Numero_Apolice' => $polNum,
                'Valor_Estorno' => 450000.00,
                'Data_Estorno' => $end->format('Y-m-d'),
                'Nome_Beneficiario' => $customer->social_denomination,
            ];
        }

        $this->kytService->runAllChecksMemory($customer, $policies, [], $refunds);

        $alert = Alert::where('entity_id', $customer->id)
            ->where('type', 'Abuso do ciclo de vida das apólices')
            ->first();

        $this->assertNotNull($alert, 'Alerta de abuso do ciclo de vida não foi criado');
    }

    public function test_high_premium_low_risk_individual(): void
    {
        $customer = Entities::create([
            'entity_type' => TypeEntity::SINGULAR->value,
            'customer_number' => 'KYT-E2E-PREM-IND-01',
        ]);

        $policies = [
            [
                'numero_apolice' => 'POL-HP-IND-001',
                'descricao_produto' => 'SEGURO BAI VIDA',
                'capital' => 1000000.00,
                'premium_total' => 150000.00,
                'data_inicio' => now()->subDays(5)->format('Y-m-d'),
                'estado_apolice' => 'NORMAL',
            ],
        ];

        $this->kytService->runAllChecksMemory($customer, $policies);

        $alert = Alert::where('entity_id', $customer->id)
            ->where('type', 'Prémio elevado incompatível com capacidade financeira')
            ->first();

        $this->assertNotNull($alert, 'Alerta de prémio elevado incompatível (individual) não foi criado');
    }

    public function test_high_premium_low_risk_collective(): void
    {
        $customer = Entities::create([
            'entity_type' => TypeEntity::COLECTIVA->value,
            'customer_number' => 'KYT-E2E-PREM-COL-01',
        ]);

        $policies = [
            [
                'numero_apolice' => 'POL-HP-COL-001',
                'descricao_produto' => 'SEGURO DE POUPANÇA VIDA (SPV) GRUPO FECHADO',
                'capital' => 2000000.00,
                'premium_total' => 600000.00,
                'data_inicio' => now()->subDays(5)->format('Y-m-d'),
                'estado_apolice' => 'NORMAL',
            ],
        ];

        $this->kytService->runAllChecksMemory($customer, $policies);

        $alert = Alert::where('entity_id', $customer->id)
            ->where('type', 'Prémio elevado incompatível com capacidade financeira')
            ->first();

        $this->assertNotNull($alert, 'Alerta de prémio elevado incompatível (colectiva) não foi criado');
    }

    public function test_all_rules_in_one_run_individual(): void
    {
        $customer = Entities::create([
            'entity_type' => TypeEntity::SINGULAR->value,
            'customer_number' => 'KYT-E2E-ALL-IND-01',
        ]);

        $baseDate = now()->subDays(30);

        $policies = [
            [
                'numero_apolice' => 'POL-ALL-REF',
                'descricao_produto' => 'SEGURO BAI VIDA',
                'capital' => 1000000.00,
                'premium_total' => 50000.00,
                'data_inicio' => (clone $baseDate)->format('Y-m-d'),
                'data_fim' => (clone $baseDate)->addDays(20)->format('Y-m-d'),
                'estado_apolice' => 'CANCELADA',
            ],
            [
                'numero_apolice' => 'POL-ALL-NEW',
                'descricao_produto' => 'SEGURO BAI VIDA',
                'capital' => 1800000.00,
                'premium_total' => 90000.00,
                'data_inicio' => (clone $baseDate)->addDays(15)->format('Y-m-d'),
                'data_fim' => null,
                'estado_apolice' => 'NORMAL',
            ],
        ];

        $refunds = [
            [
                'Numero_Apolice' => 'POL-ALL-REF',
                'Valor_Estorno' => 45000.00,
                'Data_Estorno' => (clone $baseDate)->addDays(25)->format('Y-m-d'),
                'Nome_Beneficiario' => $customer->social_denomination,
            ],
        ];

        $this->kytService->runAllChecksMemory($customer, $policies, [], $refunds);

        $alerts = Alert::where('entity_id', $customer->id)->get();

        $this->assertGreaterThanOrEqual(1, $alerts->count(), 'Nenhum alerta foi criado');
    }

    public function test_all_rules_in_one_run_collective(): void
    {
        $customer = Entities::create([
            'entity_type' => TypeEntity::COLECTIVA->value,
            'customer_number' => 'KYT-E2E-ALL-COL-01',
        ]);

        $baseDate = now()->subDays(30);

        $policies = [
            [
                'numero_apolice' => 'POL-ALL-COL-REF',
                'descricao_produto' => 'SEGURO DE POUPANÇA VIDA (SPV) GRUPO FECHADO',
                'capital' => 5000000.00,
                'premium_total' => 250000.00,
                'data_inicio' => (clone $baseDate)->format('Y-m-d'),
                'data_fim' => (clone $baseDate)->addDays(20)->format('Y-m-d'),
                'estado_apolice' => 'CANCELADA',
            ],
            [
                'numero_apolice' => 'POL-ALL-COL-NEW',
                'descricao_produto' => 'SEGURO DE POUPANÇA VIDA (SPV) GRUPO FECHADO',
                'capital' => 9000000.00,
                'premium_total' => 450000.00,
                'data_inicio' => (clone $baseDate)->addDays(15)->format('Y-m-d'),
                'data_fim' => null,
                'estado_apolice' => 'NORMAL',
            ],
        ];

        $refunds = [
            [
                'Numero_Apolice' => 'POL-ALL-COL-REF',
                'Valor_Estorno' => 200000.00,
                'Data_Estorno' => (clone $baseDate)->addDays(25)->format('Y-m-d'),
                'Nome_Beneficiario' => $customer->social_denomination,
            ],
        ];

        $this->kytService->runAllChecksMemory($customer, $policies, [], $refunds);

        $alerts = Alert::where('entity_id', $customer->id)->get();

        $this->assertGreaterThanOrEqual(1, $alerts->count(), 'Nenhum alerta foi criado para colectiva');
    }

    public function test_multiple_short_policies_individual(): void
    {
        $customer = Entities::create([
            'entity_type' => TypeEntity::SINGULAR->value,
            'customer_number' => 'KYT-E2E-SHORT-IND-01',
        ]);

        $baseDate = now()->subDays(30);

        $policies = [];
        $products = [
            'VIAGEM',
            'VIAGEM E ASSISTÊNCIA',
            'AMPARO FAMILIAR',
        ];

        for ($i = 0; $i < count($products); $i++) {
            $policies[] = [
                'numero_apolice' => "POL-SHORT-IND-{$i}",
                'descricao_produto' => $products[$i],
                'capital' => 500000.00,
                'premium_total' => 25000.00,
                'data_inicio' => (clone $baseDate)->addDays($i * 15)->format('Y-m-d'),
                'estado_apolice' => 'NORMAL',
            ];
        }

        $this->kytService->runAllChecksMemory($customer, $policies);

        $alert = Alert::where('entity_id', $customer->id)
            ->where('type', 'Múltiplas apólices de curta duração')
            ->first();

        $this->assertNotNull($alert, 'Alerta de múltiplas apólices curtas (individual) não foi criado');
    }

    public function test_multiple_short_policies_collective(): void
    {
        $customer = Entities::create([
            'entity_type' => TypeEntity::COLECTIVA->value,
            'customer_number' => 'KYT-E2E-SHORT-COL-01',
        ]);

        $baseDate = now()->subDays(45);

        $policies = [];
        $products = [
            'AC.PESSOAIS GRUPO',
            'AC.PESSOAIS GRUPO',
            'AC.PESSOAIS GRUPO',
            'GRUPO-CAPITAL PESSOAS DIVERSAS',
            'GRUPO-CAPITAL PESSOAS DIVERSAS',
        ];

        for ($i = 0; $i < count($products); $i++) {
            $policies[] = [
                'numero_apolice' => "POL-SHORT-COL-{$i}",
                'descricao_produto' => $products[$i],
                'capital' => 2000000.00,
                'premium_total' => 100000.00,
                'data_inicio' => (clone $baseDate)->addDays($i * 10)->format('Y-m-d'),
                'estado_apolice' => 'NORMAL',
            ];
        }

        $this->kytService->runAllChecksMemory($customer, $policies);

        $alert = Alert::where('entity_id', $customer->id)
            ->where('type', 'Múltiplas apólices de curta duração')
            ->first();

        $this->assertNotNull($alert, 'Alerta de múltiplas apólices curtas (colectiva) não foi criado');
    }

    public function test_multiple_short_policies_excluded_products(): void
    {
        $customer = Entities::create([
            'entity_type' => TypeEntity::SINGULAR->value,
            'customer_number' => 'KYT-E2E-SHORT-EXC-01',
        ]);

        $baseDate = now()->subDays(30);

        $policies = [
            [
                'numero_apolice' => 'POL-EXC-001',
                'descricao_produto' => 'INCENDIO/RISCO INDUSTRIAL',
                'capital' => 5000000.00,
                'premium_total' => 250000.00,
                'data_inicio' => (clone $baseDate)->format('Y-m-d'),
                'estado_apolice' => 'NORMAL',
            ],
            [
                'numero_apolice' => 'POL-EXC-002',
                'descricao_produto' => 'INCENDIO/RISCO INDUSTRIAL',
                'capital' => 3000000.00,
                'premium_total' => 150000.00,
                'data_inicio' => (clone $baseDate)->addDays(10)->format('Y-m-d'),
                'estado_apolice' => 'NORMAL',
            ],
            [
                'numero_apolice' => 'POL-EXC-003',
                'descricao_produto' => 'INCENDIO/RISCO INDUSTRIAL',
                'capital' => 4000000.00,
                'premium_total' => 200000.00,
                'data_inicio' => (clone $baseDate)->addDays(20)->format('Y-m-d'),
                'estado_apolice' => 'NORMAL',
            ],
        ];

        $this->kytService->runAllChecksMemory($customer, $policies);

        $alert = Alert::where('entity_id', $customer->id)
            ->where('type', 'Múltiplas apólices de curta duração')
            ->first();

        $this->assertNull($alert, 'Alerta falso positivo para produtos excluídos');
    }

    public function test_third_party_payments_individual(): void
    {
        $customer = Entities::create([
            'entity_type' => TypeEntity::SINGULAR->value,
            'customer_number' => 'KYT-E2E-TPP-IND-01',
        ]);

        $policies = [
            [
                'numero_apolice' => 'POL-TPP-IND-001',
                'descricao_produto' => 'SEGURO BAI VIDA',
                'capital' => 5000000.00,
                'premium_total' => 350000.00,
                'data_inicio' => now()->subDays(10)->format('Y-m-d'),
                'estado_apolice' => 'NORMAL',
            ],
        ];

        $this->kytService->runAllChecksMemory($customer, $policies);

        $alert = Alert::where('entity_id', $customer->id)
            ->where('type', 'Pagamentos de prémios por terceiros')
            ->first();

        $this->assertNotNull($alert, 'Alerta de pagamentos por terceiros (individual) não foi criado');
    }

    public function test_third_party_payments_collective(): void
    {
        $customer = Entities::create([
            'entity_type' => TypeEntity::COLECTIVA->value,
            'customer_number' => 'KYT-E2E-TPP-COL-01',
        ]);

        $policies = [
            [
                'numero_apolice' => 'POL-TPP-COL-001',
                'descricao_produto' => 'SEGURO DE POUPANÇA VIDA (SPV) GRUPO FECHADO',
                'capital' => 50000000.00,
                'premium_total' => 1500000.00,
                'data_inicio' => now()->subDays(10)->format('Y-m-d'),
                'estado_apolice' => 'NORMAL',
            ],
        ];

        $this->kytService->runAllChecksMemory($customer, $policies);

        $alert = Alert::where('entity_id', $customer->id)
            ->where('type', 'Pagamentos de prémios por terceiros')
            ->first();

        $this->assertNotNull($alert, 'Alerta de pagamentos por terceiros (colectiva) não foi criado');
    }

    public function test_third_party_payments_below_threshold(): void
    {
        $customer = Entities::create([
            'entity_type' => TypeEntity::SINGULAR->value,
            'customer_number' => 'KYT-E2E-TPP-BELOW-01',
        ]);

        $policies = [
            [
                'numero_apolice' => 'POL-TPP-BELOW-001',
                'descricao_produto' => 'SEGURO BAI VIDA',
                'capital' => 500000.00,
                'premium_total' => 50000.00,
                'data_inicio' => now()->subDays(10)->format('Y-m-d'),
                'estado_apolice' => 'NORMAL',
            ],
        ];

        $this->kytService->runAllChecksMemory($customer, $policies);

        $alert = Alert::where('entity_id', $customer->id)
            ->where('type', 'Pagamentos de prémios por terceiros')
            ->first();

        $this->assertNull($alert, 'Alerta criado abaixo do limiar');
    }

    public function test_frequent_beneficiary_changes_individual(): void
    {
        $customer = Entities::create([
            'entity_type' => TypeEntity::SINGULAR->value,
            'customer_number' => 'KYT-E2E-BEN-IND-01',
        ]);

        $baseDate = now()->subDays(90);
        $polNum = 'POL-BEN-IND-001';

        $policies = [
            [
                'numero_apolice' => $polNum,
                'descricao_produto' => 'SEGURO BAI VIDA',
                'capital' => 5000000.00,
                'premium_total' => 250000.00,
                'data_inicio' => (clone $baseDate)->format('Y-m-d'),
                'estado_apolice' => 'NORMAL',
            ],
        ];

        $beneficiaries = [];
        for ($i = 0; $i < 3; $i++) {
            $beneficiaries[] = [
                'numero_apolice' => $polNum,
                'nome_beneficiario' => 'MARIA DOS SANTOS',
                'data_atualizacao_beneficiario' => (clone $baseDate)->addDays($i * 30)->format('Y-m-d'),
            ];
        }

        $this->kytService->runAllChecksMemory($customer, $policies, [], [], [], $beneficiaries);

        $alert = Alert::where('entity_id', $customer->id)
            ->where('type', 'Alterações frequentes de beneficiários')
            ->first();

        $this->assertNotNull($alert, 'Alerta de alterações frequentes de beneficiários (individual) não foi criado');
    }

    public function test_frequent_beneficiary_changes_collective(): void
    {
        $customer = Entities::create([
            'entity_type' => TypeEntity::COLECTIVA->value,
            'customer_number' => 'KYT-E2E-BEN-COL-01',
        ]);

        $baseDate = now()->subDays(45);
        $polNum = 'POL-BEN-COL-001';

        $policies = [
            [
                'numero_apolice' => $polNum,
                'descricao_produto' => 'SEGURO DE POUPANÇA VIDA (SPV) GRUPO FECHADO',
                'capital' => 50000000.00,
                'premium_total' => 2500000.00,
                'data_inicio' => (clone $baseDate)->format('Y-m-d'),
                'estado_apolice' => 'NORMAL',
            ],
        ];

        $beneficiaries = [];
        for ($i = 0; $i < 2; $i++) {
            $beneficiaries[] = [
                'numero_apolice' => $polNum,
                'nome_beneficiario' => 'JOAO SILVA',
                'data_atualizacao_beneficiario' => (clone $baseDate)->addDays($i * 30)->format('Y-m-d'),
            ];
        }

        $this->kytService->runAllChecksMemory($customer, $policies, [], [], [], $beneficiaries);

        $alert = Alert::where('entity_id', $customer->id)
            ->where('type', 'Alterações frequentes de beneficiários')
            ->first();

        $this->assertNotNull($alert, 'Alerta de alterações frequentes de beneficiários (colectiva) não foi criado');
    }

    public function test_frequent_beneficiary_changes_insufficient(): void
    {
        $customer = Entities::create([
            'entity_type' => TypeEntity::SINGULAR->value,
            'customer_number' => 'KYT-E2E-BEN-INS-01',
        ]);

        $baseDate = now()->subDays(90);
        $polNum = 'POL-BEN-INS-001';

        $policies = [
            [
                'numero_apolice' => $polNum,
                'descricao_produto' => 'SEGURO BAI VIDA',
                'capital' => 5000000.00,
                'premium_total' => 250000.00,
                'data_inicio' => (clone $baseDate)->format('Y-m-d'),
                'estado_apolice' => 'NORMAL',
            ],
        ];

        $beneficiaries = [
            [
                'numero_apolice' => $polNum,
                'nome_beneficiario' => 'MARIA DOS SANTOS',
                'data_atualizacao_beneficiario' => (clone $baseDate)->format('Y-m-d'),
            ],
        ];

        $this->kytService->runAllChecksMemory($customer, $policies, [], [], [], $beneficiaries);

        $alert = Alert::where('entity_id', $customer->id)
            ->where('type', 'Alterações frequentes de beneficiários')
            ->first();

        $this->assertNull($alert, 'Alerta criado sem eventos suficientes');
    }

    public function test_high_risk_geography_individual(): void
    {
        $customer = Entities::create([
            'entity_type' => TypeEntity::SINGULAR->value,
            'customer_number' => 'KYT-E2E-GEO-IND-01',
        ]);

        $policies = [
            [
                'numero_apolice' => 'POL-GEO-IND-001',
                'descricao_produto' => 'VIAGEM',
                'capital' => 500000.00,
                'premium_total' => 300000.00,
                'data_inicio' => now()->subDays(5)->format('Y-m-d'),
                'estado_apolice' => 'NORMAL',
            ],
        ];

        $this->kytService->runAllChecksMemory($customer, $policies);

        $alert = Alert::where('entity_id', $customer->id)
            ->where('type', 'Alto risco geográfico')
            ->first();

        $this->assertNotNull($alert, 'Alerta de alto risco geográfico (individual) não foi criado');
    }

    public function test_high_risk_geography_collective(): void
    {
        $customer = Entities::create([
            'entity_type' => TypeEntity::COLECTIVA->value,
            'customer_number' => 'KYT-E2E-GEO-COL-01',
        ]);

        $policies = [
            [
                'numero_apolice' => 'POL-GEO-COL-001',
                'descricao_produto' => 'MERCADORIA TRANSPORTADAS/MARITIMO',
                'capital' => 50000000.00,
                'premium_total' => 2000000.00,
                'data_inicio' => now()->subDays(5)->format('Y-m-d'),
                'estado_apolice' => 'NORMAL',
            ],
        ];

        $this->kytService->runAllChecksMemory($customer, $policies);

        $alert = Alert::where('entity_id', $customer->id)
            ->where('type', 'Alto risco geográfico')
            ->first();

        $this->assertNotNull($alert, 'Alerta de alto risco geográfico (colectiva) não foi criado');
    }

    public function test_high_risk_geography_below_threshold(): void
    {
        $customer = Entities::create([
            'entity_type' => TypeEntity::SINGULAR->value,
            'customer_number' => 'KYT-E2E-GEO-BELOW-01',
        ]);

        $policies = [
            [
                'numero_apolice' => 'POL-GEO-BELOW-001',
                'descricao_produto' => 'VIAGEM',
                'capital' => 50000.00,
                'premium_total' => 5000.00,
                'data_inicio' => now()->subDays(5)->format('Y-m-d'),
                'estado_apolice' => 'NORMAL',
            ],
        ];

        $this->kytService->runAllChecksMemory($customer, $policies);

        $alert = Alert::where('entity_id', $customer->id)
            ->where('type', 'Alto risco geográfico')
            ->first();

        $this->assertNull($alert, 'Alerta criado abaixo do limiar');
    }

    public function test_overpayment_refund_individual(): void
    {
        $customer = Entities::create([
            'entity_type' => TypeEntity::SINGULAR->value,
            'customer_number' => 'KYT-E2E-OPR-IND-01',
            'social_denomination' => 'João Silva',
        ]);

        $policies = [
            [
                'numero_apolice' => 'POL-OPR-IND-001',
                'descricao_produto' => 'SEGURO BAI VIDA',
                'capital' => 1000000.00,
                'premium_total' => 50000.00,
                'data_inicio' => now()->subDays(30)->format('Y-m-d'),
                'estado_apolice' => 'NORMAL',
            ],
        ];

        $refunds = [
            [
                'Numero_Apolice' => 'POL-OPR-IND-001',
                'Valor_Estorno' => 15000.00,
                'Data_Estorno' => now()->subDays(5)->format('Y-m-d'),
                'Nome_Beneficiario' => 'Maria Santos',
            ],
        ];

        $this->kytService->runAllChecksMemory($customer, $policies, [], $refunds);

        $alert = Alert::where('entity_id', $customer->id)
            ->where('type', 'Sobrepagamento de prémio com reembolso')
            ->first();

        $this->assertNotNull($alert, 'Alerta de sobrepagamento com reembolso (individual) não foi criado');
    }

    public function test_overpayment_refund_collective(): void
    {
        $customer = Entities::create([
            'entity_type' => TypeEntity::COLECTIVA->value,
            'customer_number' => 'KYT-E2E-OPR-COL-01',
            'social_denomination' => 'Empresa Lda',
        ]);

        $policies = [
            [
                'numero_apolice' => 'POL-OPR-COL-001',
                'descricao_produto' => 'SEGURO DE POUPANÇA VIDA (SPV) GRUPO FECHADO',
                'capital' => 50000000.00,
                'premium_total' => 2000000.00,
                'data_inicio' => now()->subDays(30)->format('Y-m-d'),
                'estado_apolice' => 'NORMAL',
            ],
        ];

        $refunds = [
            [
                'Numero_Apolice' => 'POL-OPR-COL-001',
                'Valor_Estorno' => 500000.00,
                'Data_Estorno' => now()->subDays(5)->format('Y-m-d'),
                'Nome_Beneficiario' => 'Outra Empresa SA',
            ],
        ];

        $this->kytService->runAllChecksMemory($customer, $policies, [], $refunds);

        $alert = Alert::where('entity_id', $customer->id)
            ->where('type', 'Sobrepagamento de prémio com reembolso')
            ->first();

        $this->assertNotNull($alert, 'Alerta de sobrepagamento com reembolso (colectiva) não foi criado');
    }

    public function test_overpayment_refund_below_threshold(): void
    {
        $customer = Entities::create([
            'entity_type' => TypeEntity::SINGULAR->value,
            'customer_number' => 'KYT-E2E-OPR-BELOW-01',
            'social_denomination' => 'João Silva',
        ]);

        $policies = [
            [
                'numero_apolice' => 'POL-OPR-BELOW-001',
                'descricao_produto' => 'SEGURO BAI VIDA',
                'capital' => 1000000.00,
                'premium_total' => 50000.00,
                'data_inicio' => now()->subDays(30)->format('Y-m-d'),
                'estado_apolice' => 'NORMAL',
            ],
        ];

        $refunds = [
            [
                'Numero_Apolice' => 'POL-OPR-BELOW-001',
                'Valor_Estorno' => 1000.00,
                'Data_Estorno' => now()->subDays(5)->format('Y-m-d'),
                'Nome_Beneficiario' => 'João Silva',
            ],
        ];

        $this->kytService->runAllChecksMemory($customer, $policies, [], $refunds);

        $alert = Alert::where('entity_id', $customer->id)
            ->where('type', 'Sobrepagamento de prémio com reembolso')
            ->first();

        $this->assertNull($alert, 'Alerta criado abaixo do limiar de 5%');
    }

    public function test_no_false_positives_low_risk_products(): void
    {
        $customer = Entities::create([
            'entity_type' => TypeEntity::SINGULAR->value,
            'customer_number' => 'KYT-E2E-FP-01',
        ]);

        $policies = [
            [
                'numero_apolice' => 'POL-LOW-001',
                'descricao_produto' => 'SEGURO ESCOLAR',
                'capital' => 500000.00,
                'premium_total' => 250000.00,
                'data_inicio' => now()->subDays(5)->format('Y-m-d'),
                'estado_apolice' => 'NORMAL',
            ],
        ];

        $this->kytService->runAllChecksMemory($customer, $policies);

        $alert = Alert::where('entity_id', $customer->id)
            ->where('type', 'Prémio elevado incompatível com capacidade financeira')
            ->first();

        $this->assertNull($alert, 'Alerta falso positivo para produto de baixo risco');
    }
}
