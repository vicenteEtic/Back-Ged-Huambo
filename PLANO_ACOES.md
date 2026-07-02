# Plano de Ações — KYT Dynamic Rules System

## Estado Atual
- ✅ Código core implementado (DynamicKYTService, 6 handlers + DefaultRuleHandler, API CRUD, migrations, seeder, jobs)
- ✅ Jobs corrigidos (cursor pagination, chunkById, $tries=10)
- ✅ Handlers funcionando com dados da DB
- ⏳ **3 issues conhecidos por resolver**
- ⏳ **Deploy no QA pendente**

---

## Issue 1: Nomes de beneficiários não aparecem nos alertas `frequent_beneficiary_changes`

### Causa Raiz
O `FrequentBeneficiaryChangesHandler` atual **só consulta `beneficiarios_staging`** e ignora `policy_changes_staging`. O antigo `KYTService` usava ambas as fontes, com fallback de extração por regex do `motivo_alteracao`.

### O que fazer

**1.1 — Reintroduzir leitura de `$changes` no handler**
- Arquivo: `app/Services/KYT/Rules/FrequentBeneficiaryChangesHandler.php`
- No método `check()`, usar o parâmetro `$changes` (já recebido mas ignorado)
- Filtrar `$changes` onde `tipo_alteracao` contém `'BENEFICIÁRIO'` / `'BENEFICIARIO'`

**1.2 — Importar lógica de extração de nomes do `motivo_alteracao`**
- Arquivo: `app/Services/KYT/Rules/FrequentBeneficiaryChangesHandler.php`
- Copiar/adaptar método `extractBeneficiaryNames()` do `KYTService.php` (linhas 1324-1361)
- Regex para padrões: `"DE X PARA Y"`, `"NOVO BENEFICIÁRIO: X"`, `"INCLUSÃO DE X"`, etc.

**1.3 — Mesclar eventos de ambas as fontes**
- Prioridade: `beneficiarios_staging` (dados estruturados)
- Fallback: `policy_changes_staging` com extração por regex
- Usar `data_alteracao` como fallback de data quando `data_atualizacao_beneficiario` não existir

**1.4 — Filtro de motivos justificados**
- Manter lógica de `interpretation_aml` para excluir: herança, casamento, divórcio, nascimento, óbito, falecimento, alteração familiar

---

## Issue 2: `indicador_pagamento_terceiro` NULL nos recibos reais

### Causa Raiz
O CSV de origem não preenche a coluna `INDICADOR_PAGAMENTO_TERCEIRO`. O handler atual tenta usar `nome_pagador` como fallback, mas pode não ser suficiente.

### O que fazer

**2.1 — Investigar colunas alternativas no CSV**
- Verificar se `CODIGO_PAGADOR`, `NIF_PAGADOR`, `RELACAO_COM_TOMADOR` têm dados consistentes
- Se `relacao_com_tomador` tiver valores como `"TERCEIRO"`, usar como indicador

**2.2 — Melhorar lógica de detecção no handler**
- Arquivo: `app/Services/KYT/Rules/ThirdPartyPaymentHandler.php`
- Comparar `nif_pagador` com NIF do cliente (se disponível no `Entities`)
- Comparar `codigo_pagador` com identificador do cliente
- Usar `relacao_com_tomador` como evidência adicional

**2.3 — Ajustar condição do alerta**
- Se `$indicator` for vazio E `$payerName` vazio E `$nif` vazio → pular (sem evidência)
- Só disparar alerta se houver pelo menos uma evidência concreta de pagamento por terceiro

---

## Issue 3: Apólice `0100153594` dispara alerta sem recibos na BD

### Causa Raiz
`ThirdPartyPaymentHandler::check()` dispara alerta sempre que `totalPremium >= threshold`, mesmo sem nenhum recibo de terceiro encontrado. Prémio 675k > threshold 300k.

### O que fazer

**3.1 — Endurecer condição de disparo**
- Opção A (recomendada): Só disparar alerta se `!empty($thirdPartyReceipts)`
- Opção B: Se prémio >= threshold mas sem recibos, criar alerta com severity menor e descrição diferente ("Prémio elevado sem confirmação de pagamento por terceiro")

**3.2 — Revisar seed de threshold**
- Verificar se 300k Kz é apropriado para `pessoa_individual` ou se deve ser mais alto
- Considerar threshold por produto em vez de global

---

## Deploy no QA

```bash
# 1. Fazer merge/Pull da develop no servidor
git pull origin develop

# 2. Rodar migration (se houver novas)
docker exec app-php php artisan migrate

# 3. Seed das regras KYT (idempotente)
docker exec app-php php artisan db:seed --class=KytRuleSeeder

# 4. Atualizar autoload
docker exec app-php composer dump-autoload

# 5. Restart worker
docker exec app-php supervisorctl restart laravel-worker
```

---

## Ordem de Execução

| Prioridade | Tarefa | Arquivos | Esforço |
|------------|--------|----------|---------|
| 🔴 P0 | 1.1 + 1.2 — Nomes beneficiários no handler | `FrequentBeneficiaryChangesHandler.php` | 2h |
| 🔴 P0 | 3.1 — Endurecer condição ThirdPartyPayment | `ThirdPartyPaymentHandler.php` | 30min |
| 🟡 P1 | 1.3 + 1.4 — Merge eventos + filtro justificados | `FrequentBeneficiaryChangesHandler.php` | 1h |
| 🟡 P1 | 2.2 — Melhorar detecção terceiro pagador | `ThirdPartyPaymentHandler.php` | 1h |
| 🟢 P2 | 2.1 — Investigar dados CSV recibos | — | investigação |
| 🟢 P2 | 3.2 — Revisar thresholds seed | `KytRuleSeeder.php` | 30min |
| 🔵 P0 | Deploy no QA | — | 15min |

---

## Conceitos-Chave

- **`$policies`**: da tabela `policies_staging`, join por `numero_apolice`
- **`$receipts`**: da tabela `recibos_cobrados`, join por `numero_apolice`
- **`$changes`**: da tabela `policy_changes_staging`, join por `numero_apolice`
- **`$refunds`**: da tabela `apol_anulada_estorno`, join por `n_apolice`
- **`$beneficiaries`**: da tabela `beneficiarios_staging`, join por `numero_apolice`
- Todas as 5 tabelas são carregadas no `ProcessCustomerPoliciesJob` e passadas para `DynamicKYTService::runAllChecksMemory()`
- Cada handler recebe todos os arrays e filtra pelos `numero_apolice` relevantes
