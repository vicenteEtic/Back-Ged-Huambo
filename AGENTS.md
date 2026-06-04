# Session Context — KYT Dynamic Rules System

## Goal
Replace hardcoded KYT rules with a dynamic DB-driven system, fix MaxAttemptsExceededException on ProcessCustomerDataJob, and ensure receipts/beneficiaries data is used in alert generation.

## Constraints
- Worker uses `database` queue driver (not Redis/Horizon)
- Rule parameters (thresholds, products, scores, description templates) must be editable via DB without code deploys
- Description templates support placeholders: `{customer}`, `{total}`, `{events}`, `{products}`, `{interpretation}`, `{payer_details}`, `{window_days}`, etc.
- Beneficiary names must appear in `frequent_beneficiary_changes` alerts
- `receipts` variable (from `recibos_cobrados`) must be used in checks

## Progress

### Code Created/Modified

| File | Type | Purpose |
|------|------|---------|
| `database/migrations/2026_06_03_160700_create_kyt_rules_tables.php` | Migration | Creates `kyt_rule_definitions` and `kyt_rule_definition_products` |
| `app/Models/KYT/KytRule.php` | Model | KytRule with `products()`, `relevantProducts()`, `excludedProducts()` |
| `app/Models/KYT/KytRuleProduct.php` | Model | Pivot model |
| `app/Services/KYT/DynamicKYTService.php` | Service | Loads active rules from DB, resolves handler, creates alerts |
| `app/Services/KYT/Rules/Contracts/RuleHandler.php` | Interface | `check()` returning `array` |
| `app/Services/KYT/Rules/DefaultRuleHandler.php` | Handler | Generic product+threshold+min_events filter |
| `app/Services/KYT/Rules/FrequentBeneficiaryChangesHandler.php` | Handler | Beneficiary change detection with name extraction |
| `app/Services/KYT/Rules/OverpaymentRefundHandler.php` | Handler | Refund vs premium cross-reference, per-product |
| `app/Services/KYT/Rules/HighCapitalIncreaseHandler.php` | Handler | Capital variation with 30d/90d thresholds |
| `app/Services/KYT/Rules/PolicyLifecycleAbuseHandler.php` | Handler | Cancelled/refunded policy detection within window |
| `app/Services/KYT/Rules/ThirdPartyPaymentHandler.php` | Handler | Reads `indicador_pagamento_terceiro` + `nome_pagador` from receipts |
| `app/Services/KYT/Rules/MultipleShortPoliciesHandler.php` | Handler | Date window between first/last policy start + min_events |
| `config/kyt.php` | Config | Maps rule slugs → handler classes |
| `database/seeders/KytRuleSeeder.php` | Seeder | Seeds 16 rule rows (8 slugs × 2 entity types) |
| `app/Jobs/ProcessCustomerPoliciesJob.php` | Job | Switched from `KYTService` to `DynamicKYTService` |
| `app/Jobs/ProcessCustomerDataJob.php` | Job | `chunkById(500)`, `$tries=10` |
| `app/Jobs/DispatchCustomerJobsJob.php` | Job | Cursor pagination instead of OFFSET chunk |

### Handler Map (config/kyt.php)
```php
'frequent_beneficiary_changes' => FrequentBeneficiaryChangesHandler
'overpayment_refund'           => OverpaymentRefundHandler
'high_capital_increase'        => HighCapitalIncreaseHandler
'policy_lifecycle_abuse'       => PolicyLifecycleAbuseHandler
'third_party_payments'         => ThirdPartyPaymentHandler
'multiple_short_policies'      => MultipleShortPoliciesHandler
```
DefaultRuleHandler is used for: `high_premium_low_risk`, `high_risk_geography`.

### Fixed Issues
- `chunk()` with OFFSET → cursor pagination (DispatchCustomerJobsJob)
- `chunk()` with OFFSET → `chunkById()` + `$tries=10` (ProcessCustomerDataJob)
- `stdClass` not cast to array in `normalizePolicies()` (old KYTService)
- `ArgumentCountError` — `$beneficiaries` param missing from `runAllChecksMemory`
- `{interpretation}` not in replacements array (DefaultRuleHandler)
- Zero-premium policies not filtered out (DefaultRuleHandler::filterZeroValues)
- `Cliente: {total}` → `Cliente: {customer}` (templates)
- `min_events` not enforced in DefaultRuleHandler
- Seeder not idempotent → added truncate before insert
- Table name conflict `kyt_rules` → `kyt_rule_definitions`
- Index name too long (MySQL 64 char limit)
- Third-party payments didn't show payer name → ThirdPartyPaymentHandler
- Multiple short policies didn't check date window → MultipleShortPoliciesHandler

### Known Issues (not yet fixed)
- **Beneficiary names not appearing** in `frequent_beneficiary_changes` alerts for some customers: `beneficiarios_staging` table has no matching records for those policy numbers, and `motivo_alteracao` extraction from `policy_changes_staging` also fails to find names. Need to investigate actual `motivo_alteracao` values.

## Next Steps on Server
1. `git pull origin develop`
2. `docker exec keepcomply-QA-php php artisan db:seed --class=KytRuleSeeder`
3. `docker exec keepcomply-QA-php supervisorctl restart laravel-worker`

## Key Files
- `app/Services/KYT/DynamicKYTService.php` — main engine
- `config/kyt.php` — handler registration
- `database/seeders/KytRuleSeeder.php` — rule definitions + products
- `app/Services/KYT/Rules/*.php` — individual rule handlers
