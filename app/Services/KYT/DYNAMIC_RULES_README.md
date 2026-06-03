# Sistema Dinâmico de Regras KYT

## Arquitetura

```
database/migrations/2026_06_03_160700_create_kyt_rules_tables.php
    ├── kyt_rule_definitions          ← Regras (slug, thresholds, template, etc)
    └── kyt_rule_definition_products  ← Produtos relevantes/excluídos por regra

app/Models/KYT/
    ├── KytRule.php        ← Model + relationships
    └── KytRuleProduct.php ← Pivot

app/Services/KYT/
    ├── DynamicKYTService.php   ← Motor principal (lê regras do DB)
    └── Rules/
        ├── Contracts/
        │   └── RuleHandler.php       ← Interface
        ├── DefaultRuleHandler.php    ← Handler genérico
        └── ... (handlers específicos)

database/seeders/KytRuleSeeder.php  ← Popula as 8 regras × 2 tipos
config/kyt.php                      ← Mapeamento slug → handler class
```

## Como funciona

1. `DynamicKYTService::runAllChecksMemory()` carrega as regras ativas da tabela `kyt_rules`
2. Para cada regra, resolve o `entity_type` (individual/collective/both) match com o cliente
3. Encontra o handler correspondente no `config/kyt.php` (ou usa `DefaultRuleHandler`)
4. O handler aplica a lógica: filtra produtos, checa threshold, monta descrição, retorna alerta
5. `DynamicKYTService` cria o alerta no banco

## Placeholders da descrição

Usa `{placeholder}` no `description_template` da regra:

| Placeholder | Valor |
|---|---|
| `{customer}` | Número do cliente |
| `{entity_type}` | Singular / Coletiva |
| `{threshold}` | Valor do threshold formatado |
| `{total}` | Valor total detetado |
| `{events}` | Nº de eventos |
| `{min_events}` | Mínimo de eventos |
| `{window_days}` | Janela real em dias |
| `{max_days}` | Janela máxima em dias |
| `{products}` | Lista de apólices |
| `{interpretation}` | Texto de interpretação AML |

## Score increments

Em `score_increments` (JSON), define condições que somam pontos:

```json
{
    "above_double_threshold": 10,
    "events_above_min": 5,
    "half_window": 5,
    "has_receipts_third_party": 10,
    "has_country_origin": 5,
    "ratio_above_20pct": 10
}
```

## Mudar do KYTService antigo para o DynamicKYTService

**Passo 1:** Rodar a migration e o seeder:

```bash
php artisan migrate
php artisan db:seed --class=DatabaseSeeder
```

**Passo 2:** Criar handlers para regras com lógica complexa.

Handlers necessários (em ordem de prioridade):
1. `FrequentBeneficiaryChangesHandler` — usa changes + beneficiaries
2. `OverpaymentRefundHandler` — cruza refunds + receipts
3. `HighCapitalIncreaseHandler` — calcula variação percentual
4. `PolicyLifecycleAbuseHandler` — verifica refunds + status

**Exemplo de handler customizado:**

```php
class FrequentBeneficiaryChangesHandler implements RuleHandler
{
    public function check(...): ?array
    {
        // Lógica específica com $changes + $beneficiaries
        // Se aplicar, retorna ['name','description','severity','score']
        // Se não, retorna null
    }
}
```

Depois registar em `config/kyt.php`:

```php
'handlers' => [
    'frequent_beneficiary_changes' => FrequentBeneficiaryChangesHandler::class,
],
```

**Passo 3:** Trocar o serviço no `ProcessCustomerPoliciesJob`:

```php
// De:
$kytService->runAllChecksMemory(...)

// Para:
$dynamicKytService = app(DynamicKYTService::class);
$dynamicKytService->runAllChecksMemory(...)
```

**Passo 4: Limpar cache se editar regras pelo frontend:**

```php
app(DynamicKYTService::class)->clearRulesCache();
```

Ou via Tinker:

```bash
php artisan tinker
> app(App\Services\KYT\DynamicKYTService::class)->clearRulesCache();
```

## Frontend (Admin)

Página necessária: `/admin/kyt-rules`

Campos do formulário de edição:
- **slug** (locked, auto)
- **name** (text)
- **entity_type** (dropdown: individual/collective/both)
- **threshold_field** (text: premium_total, capital, etc)
- **threshold_value** (number)
- **min_events** (number)
- **max_days** (number)
- **score_base** (number)
- **score_increments** (key-value editor)
- **severity** (dropdown)
- **description_template** (textarea com placeholders)
- **interpretation_aml** (textarea)
- **extra_params** (JSON editor)
- **is_active** (toggle)
- **Produtos** — autocomplete + adicionar/remover com tipo relevant/excluded

Após salvar, chamar `clearRulesCache()`.
