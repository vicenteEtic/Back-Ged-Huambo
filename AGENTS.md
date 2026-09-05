# Projecto Base — Laravel Genérico

## O que foi feito (limpeza do projecto)
- Removidos CSVs com dados reais de clientes (`clientes.csv`, `apolices_vida.csv`, `Alteracoes_Apolices.csv`)
- Genericizados: `docker-compose.yml`, `Taskfile.yaml`, `config/cors.php`, `config/services.php`, `ml-service/app.py`
- Templates de email agora usam `config('app.name')` e `config('app.logo_url')`
- Mail classes usam `env()` em vez de endereços hardcoded
- Cache de views limpa (`storage/framework/views/`)
- Repositório: `vicenteEtic/Back-Ged-Huambo`

---

# Plano — Sistema de RH (Recursos Humanos)

## Arquitectura Base (já existe)
- Laravel com auth (Sanctum)
- Sistema de permissões (roles/permissions)
- API CRUD padronizada (Repository + Service + Controller + Request)
- Módulo KYT existente (pode servir de referência para novos módulos)

---

## FASE 1 — Fundação (Core)

### 1.1 Departamentos
| Item | Descrição |
|------|-----------|
| Tabela | `departments` (id, nome, codigo, descricao, responsavel_id, parent_id, activo, timestamps) |
| CRUD | Repository + Service + Controller + FormRequest |
| API | `/api/v1/rh/departments` |

### 1.2 Cargos
| Item | Descrição |
|------|-----------|
| Tabela | `positions` (id, nome, codigo, descricao, department_id, nivel_hierarquico, salario_base, requisitos, activo, timestamps) |
| CRUD | Repository + Service + Controller + FormRequest |
| API | `/api/v1/rh/positions` |

### 1.3 Funcionários
| Item | Descrição |
|------|-----------|
| Tabela | `employees` (id, user_id, numero_funcionario, nome_completo, data_nascimento, genero, estado_civil, nacionalidade, documento_tipo, documento_numero, nif, email_pessoal, telefone, endereco, departamento_id, cargo_id, data_admissao, data_efetivacao, tipo_contracto, salario_base, banco_nome, banco_iban, status, foto_url, timestamps) |
| CRUD | Repository + Service + Controller + FormRequest + Import/Export |
| API | `/api/v1/rh/employees` |
| Relação | Ligar User (auth) a Employee |

### 1.4 Tabelas de Apoio
| Tabela | Descrição |
|--------|-----------|
| `contract_types` | Tipos de contrato (efectivo, prestação serviços, estagiário, etc) |
| `document_types` | BI, Passaporte, Carta de Condução, etc |
| `countries` | Países (nacionalidade) |
| `banks` | Lista de bancos |

---

## FASE 2 — Operacional

### 2.1 Férias e Licenças
| Item | Descrição |
|------|-----------|
| Tabelas | `leave_types` (tipo: férias, licença médica, luto, casamento, paternidade, etc), `leave_requests` (pedidos com datas, motivo, status, aprovador) |
| Regras | Saldo de dias por tipo, anos de serviço, acumulação |
| Fluxo | Pedido → Aprovação hierárquica → Registado |
| API | `/api/v1/rh/leaves` |

### 2.2 Ponto / Frequência
| Item | Descrição |
|------|-----------|
| Tabelas | `attendance` (employee_id, data, hora_entrada, hora_saida, horas_trabalhadas, justificacao), `attendance_import_logs` |
| Funcionalidades | Registo manual, importação por CSV, relatório de atrasos/faltas |
| API | `/api/v1/rh/attendance` |

### 2.3 Documentos dos Funcionários
| Item | Descrição |
|------|-----------|
| Tabela | `employee_documents` (id, employee_id, document_type, nome, descricao, file_path, validade, timestamps) |
| Upload | Media Library / Storage local + S3 |
| Alertas | Documentos próximos do vencimento |
| API | `/api/v1/rh/employees/{id}/documents` |

---

## FASE 3 — Avançado

### 3.1 Folha de Pagamento
| Item | Descrição |
|------|-----------|
| Tabelas | `payroll_periods`, `payroll_items`, `payroll_descontos`, `payroll_subsidios`, `payroll_historico` |
| Cálculos | Salário base + subsídios (transporte, alimentação) - descontos (INSS, IRT) |
| Exportação | Relatório PDF/Excel por período |
| API | `/api/v1/rh/payroll` |

### 3.2 Recrutamento e Seleção
| Item | Descrição |
|------|-----------|
| Tabelas | `job_openings` (vaga, departamento, cargo, requisitos, status), `candidates` (dados, curriculo), `applications` (candidatura a vaga), `interviews` (entrevistas, avaliadores, resultado) |
| Fluxo | Vaga → Candidatura → Triagem → Entrevista → Contratação |
| API | `/api/v1/rh/recruitment` |

### 3.3 Formação e Desenvolvimento
| Item | Descrição |
|------|-----------|
| Tabelas | `training_courses`, `training_sessions`, `training_enrollments`, `training_certificates` |
| Funcionalidades | Plano anual de formação, inscrições, certificados, validade |
| API | `/api/v1/rh/training` |

### 3.4 Avaliação de Desempenho
| Item | Descrição |
|------|-----------|
| Tabelas | `performance_cycles`, `performance_goals`, `performance_evaluations`, `performance_feedback`, `performance_competencias` |
| Métodos | Autoavaliação, avaliação do superior, 360º |
| API | `/api/v1/rh/performance` |

### 3.5 Benefícios e Incentivos
| Item | Descrição |
|------|-----------|
| Tabelas | `benefit_types`, `employee_benefits`, `benefit_claims` |
| Exemplos | Seguro de saúde, subsídio de estudo, prémios |
| API | `/api/v1/rh/benefits` |

### 3.6 Ocorrências Disciplinares
| Item | Descrição |
|------|-----------|
| Tabelas | `disciplinary_types`, `disciplinary_records` |
| Fluxo | Registo de ocorrência → Investigação → Decisão → Arquivo |
| API | `/api/v1/rh/disciplinary` |

### 3.7 Relatórios e Dashboard
| Item | Descrição |
|------|-----------|
| Dashboard | Total funcionários, distribuição por departamento, género, tipo contrato, aniversariantes do mês |
| Relatórios | Mapa de pessoal, evolução salarial, controle de férias, ponto mensal, rotatividade |
| Exportação | PDF, Excel |

---

## FASE 4 — Integrações e Extra

### 4.1 Login com PIN/QR (para ponto)
### 4.2 Envio de Notificações (email/SMS) para aniversários, documentos a vencer
### 4.3 Workflow de Aprovações (delegação, níveis, hierarquia)
### 4.4 Importação massiva de funcionários via Excel/CSV
### 4.5 Auditoria (logs de todas as alterações em dados sensíveis)

---

## Stack Técnica (sugerida)
- **Padrão**: Repository + Service + Controller (igual ao módulo KYT)
- **Validação**: FormRequest por módulo
- **Media**: Laravel Media Library ou Storage
- **Relatórios**: Laravel Excel / dompdf / barryvdh/laravel-dompdf
- **Notificações**: Laravel Notifications (database + mail)

---

# Progresso Real (Implementado)

## FLUXO 8 — Gestão de Carreiras ✅
| Item | Status |
|------|--------|
| `career_service_times` | migration com campos `institution_entry_date`, `category` em employees (`career_regime` removido em Aug 2026) |
| `CareerService` | cálculo de tempo total, tempo na categoria, cargo, instituição |
| API | `GET /api/v1/rh/career`, `GET /api/v1/rh/career/{id}` |

## FLUXO 9 — Avaliação de Desempenho (enhanced) ✅
| Item | Status |
|------|--------|
| `evaluation_criteria` + `evaluation_scores` | critérios e pontuações por avaliação |
| Weighted score | cálculo automático com classificação (Excelente/Bom/Satisfatório/Suficiente/Insuficiente) |
| API | CRUD criteria/scores + `POST /evaluations/{id}/calculate` |

## FLUXO 10 — Progressões e Promoções ✅
| Item | Status |
|------|--------|
| `progression_rules` | regras de elegibilidade (min months, min score, level) |
| `progression_requests` | fluxo de solicitação |
| `progression_approvals` | cadeia hierárquica de aprovação |
| Execução | atualiza employee (categoria/cargo/salário) + regista em `functional_history` |
| API | CRUD + `check-eligibility` + `approve`/`reject`/`execute` |

## FLUXO 11 — Gestão de Férias e Licenças (enhanced) ✅
| Item | Status |
|------|--------|
| `leave_plans` | planeamento anual por employee com balanço auto-sync |
| `leave_approvals` | aprovação hierárquica multi-nível |
| `POST /leave-requests` | cálculo automático de dias úteis, ligação ao plano |
| `GET /leaves/calendar` | calendário com filtro ano/departamento |
| Balanço | endpoint por employee |

## FLUXO 12 — Ponto e Assiduidade (enhanced) ✅
> **Nota (Set 2026)**: turnos removidos — `shifts`/`shift_assignments` eliminadas (migration `2026_09_05_000001`). O registo de ponto deixou de calcular atrasos/horas extra (valores neutros: `late_minutes`/`overtime_minutes` a 0, `expected_check_in/out`/`shift_id` nulos). A coluna `shift_id` mantém-se na tabela `attendance` com valores nulos.
> **Nota (Set 2026) — Funcionários de férias**: funcionários com `leave_requests.status='approved'` cobrindo a data estão **bloqueados para registo de ponto** (check-in/check-out, CRUD store/update via `AttendanceRequest::notOnLeave()`, `registerAbsence`, `importBiometric` e justificação de falta manual em `AbsenceJustificationService`) e não são marcados como faltas pelo sistema automático (`markAbsentForDate` devolve `on_leave_skipped`). `GET /api/rh/attendance/employees-for-point` devolve funcionários activos com flag `on_leave`, `display_name="Nome — De férias"`, período e mensagem informativa para o select.
> **Nota (Set 2026) — Excepção de gabinetes (livro de ponto próprio)**: GEPE, Gabinete do Governador e Comunicação Social **não assinam o livro de ponto no RH**. A regra é **configurável** em `config/rh.php` (`ponto.exempt_department_codes` por código e `ponto.exempt_department_names` por nome) e centralizada em `App\Support\PontoExceptions`. Funcionários desses departamentos **não aparecem** em `employees-for-point` (nem no registo de ponto), estão bloqueados em check-in/check-out, CRUD, `registerAbsence`, `importBiometric`, justificação manual de falta, e são ignorados pelo `markAbsentForDate` (devolve `exempt_skipped`).
| Item | Status |
|------|--------|
| ~~`shifts`~~ | ~~turnos com minutos de tolerância~~ (removido Set 2026) |
| ~~`shift_assignments`~~ | ~~alocação por employee com datas~~ (removido Set 2026) |
| `POST check-in` / `POST check-out` | registo de ponto (sem cálculo de turno; bloqueado p/ funcionários de férias) |
| `POST absence` | registo de falta com justificação (bloqueado p/ funcionários de férias) |
| `POST import-biometric` | importação de CSV biométrico com logging (linhas de férias falham) |
| `GET employees-for-point` | select de funcionários com flag `on_leave` + "Nome — De férias" (exclui gabinetes com excepção) |
| `GET /records` (`GET /`) | **listagem de assiduidade** — padrão = dia actual; filtros `period` (today/yesterday/day_before_yesterday/this_week/last_week/this_month/last_month/last_3_months/last_6_months/this_year), `date`, `start_date`+`end_date`, `employee_id`, `paginate`; cada registo com `employee_number`; devolve `records` + `summary` + `filters` + `periods` |
| `GET /employees/{employee_id}/assiduidade` | assiduidade de um funcionário por período (`period`: 1_day/3_days/1_week/1_month/3_months/6_months/1_year, ou `date`/`start_date`+`end_date`) com `records` + `summary` + `working_days`/`on_leave_days` |
| Excepção de gabinetes | `config/rh.php` → `App\Support\PontoExceptions` — GEPE/GAB-GOV/GAB-COM não assinam ponto no RH (excluídos também da listagem) |
| Relatório mensal | por employee |

## FLUXO 13 — Documentos dos Funcionários ✅
| Item | Status |
|------|--------|
| `employee_documents` | upload, tipo, validade |
| Notificações | `DocumentExpiryNotification` com comando `rh:check-document-expiry` |
| API | `/api/v1/rh/employees/{id}/documents` |

## FLUXO 14 — Feriados e Dia de Voltar ✅
| Item | Status |
|------|--------|
| `holidays` | tabela com `name`, `date`, `recurrent`, `is_active` (SoftDeletes) |
| CRUD | Repository + Service + Controller + FormRequest (padrão Abstract) |
| Sincronização | `syncFromNager()` via `date.nager.at` — nomes sempre em português (`localName` + mapa de tradução inglês→PT) |
| Endpoint | `POST /api/rh/leaves/holidays/sync` (`year`/`country`, default AO) |
| Comando | `php artisan rh:sync-holidays {--year=} {--country=AO}` |
| Seeder | `HolidaySeeder` tenta a API para ano actual+seguinte, com fallback estático (Lei 20/93) |
| Dia de voltar | `return_date` em `leave_requests` — próximo dia útil após `end_date`, saltando fins-de-semana e feriados (`LeaveRequestService::calculateReturnDate()`) |
| Permissões | `RH Feriados` (`rh-feriados-show/create/edit/delete`) |
| API | `/api/rh/leaves/holidays` |

## FLUXO 15 — Títulos de Vencimento (Payslips) ✅
| Item | Status |
|------|--------|
| `payslips` | tabela com breakdown completo (base, subsídios, descontos, líquido) |
| Geração | `POST generate/{period_id}` a partir de `payroll_items` |
| Histórico | por employee + tracking de download |
| API | `/api/v1/rh/payslips` |

## FLUXO 16 — Benefícios Sociais (enhanced) ✅
| Item | Status |
|------|--------|
| `benefit_types.category` | campo `subsidy`, `medical`, `social_support`, `institutional`, `other` |
| `benefit_claims` | pedidos com fluxo aprovação |
| `medical_assistance` | assistência médica tracking |
| API | `/api/v1/rh/benefits/claims`, `/api/v1/rh/benefits/medical` |

## FLUXO 17 — Aposentação e Reforma ✅
| Item | Status |
|------|--------|
| `retirement_eligibility` | verificação idade + contribuições com data esperada |
| `retirement_processes` | workflow completo (draft → approved → concluded) |
| `post_retirement_history` | histórico pós-reforma |
| API | `/api/v1/rh/retirement` |

## FLUXO 18 — Portal do Funcionário ✅
| Item | Status |
|------|--------|
| `EmployeePortalController` | endpoints read-only scoped ao user autenticado |
| Profile, Saldo férias, Histórico salarial, Carreira, Benefícios |
| Download de payslip | `POST /portal/payslip/{id}/download` |
| API | `/api/v1/rh/portal` |

## FLUXO 19 — Gestão de Arquivos ✅
| Item | Status |
|------|--------|
| `archive_categories` | árvore hierárquica (processo_individual, administrativo, relatorio, avaliacao, despacho) |
| `archive_documents` | docs com metadados, tags, confidencialidade, aprovação |
| `archive_document_versions` | controlo de versões |
| `archive_document_shares` | partilhas com users/employees (view/download/edit + validade) |
| Pesquisa avançada | `GET /search?q=&type=&status=&confidentiality=` |
| API | `/api/v1/rh/archive` |

## FLUXO 20 — Emissão de Declarações (campos dinâmicos + DOCX) ✅
| Item | Status |
|------|--------|
| 17 tipos de declaração | `DeclarationTypeEnum` + seeder com `code/name/description/requires_approval` |
| Formulário dinâmico | `GET /api/rh/declarations/types/{code}/fields` devolve campos comuns + específicos do tipo (metadados em `config/declaracoes.php`) |
| `numero_declaracao` | **auto-gerado** pelo backend: sequência anual `0001/GAB-RH/2026` (campo marcado `derived` no config — o frontend não o envia) |
| Prefill da BD | `nome_completo`, `sexo`, `categoria_funcao`, `cargo`, `local_servico`, `vinculo`, `banco`, `numero_conta`, `salario_numero` (base), `salario_numero_liquido` (payslip), `data_admissao`, `numero_bi`, `telefone`, `email`, `morada`, `tratamento`, `tempo_servico`, `entidade_empregadora`, `departamento_emissor` são preenchidos do funcionário (valores enviados têm prioridade) |
| Base de dados | **Todos** os campos de declaração como colunas **nuláveis** em `declaration_requests` (37 colunas: comuns, quase-comuns e específicos) |
| `salario_extenso` | gerado automaticamente a partir de `salario_numero` (`App\Support\NumberToWordsPt`, PT) |
| `data_emissao` por extenso | "aos 30 de Março de 2026" (`App\Support\DeclarationText::dateSentence`) |
| Género derivado | `sexo` determina Senhor/Senhora, o/a, do/da, funcionário(a) |
| Geração `.docx` | `DeclarationDocxService` (phpoffice/phpword) com cabeçalho oficial REPÚBLICA DE ANGOLA / GOVERNO DA PROVÍNCIA DO HUAMBO / Gabinete de Recursos Humanos |
| Download | `GET /api/rh/declarations/{id}/download` (Content-Type `application/vnd...wordprocessingml.document`) |
| API | `/api/rh/declarations` (+ `types/{code}/fields`, `{id}/download`) |
| Validação | enums `sexo`, `tipo_salario`, `tipo_correccao`; campos salariais numéricos |

> Nota: `informacao_salarial` usa `salario_numero` (base) + `salario_numero_liquido`/`salario_extenso_liquido` para o líquido.

## FLUXO 21 — Solicitações de Dispensa (Solicitações/Dispensas) ✅
| Item | Status |
|------|--------|
| `attendance_request_types` | **tipos cadastráveis/edítáveis via API** — tabela própria (code, name, description, `required_documents` JSON, `max_days`, `legal_ref`, is_active, sort_order); semeada por `AttendanceRequestTypeSeed` a partir de `config/rh.php` → `dispensa.types`; registry central em `App\Support\Dispensa` (DB com fallback config). Tipos: `dispensa`, `amamentacao`, `pre_natal`, `relatorio_medico` (máx. 30 dias), `acompanhamento_deficiencia`, `outro` |
| `attendance_requests` | solicitações com `request_number` (`RD/0001/2026`), estado (pendente/em análise/aprovada/rejeitada/cancelada), benefícios, parecer, despacho |
| `attendance_request_documents` | anexos com `document_type` (cédula da criança, título de alta, BI da mãe, requerimento, relatório médico, comprovativo) via `FileUploadService` |
| `attendance_request_logs` | auditoria completa de todos os passos (criada, em análise, aprovada, rejeitada, cancelada, eliminada, despacho, expirada) |
| Documentos obrigatórios | validados por tipo (`require_documents` no config) — em falta → 422 com nomes em PT |
| Validações | período (`end >= start`), `max_days` por tipo (**30 dias** para relatório médico — art. 90.º, acima → Junta Médica), sobreposição com solicitação activa (`pending/under_review/approved`), bloqueio se funcionário de férias no período, **imutável após decidida** |
| Regra amamentação | apenas dispensa parcial (redução diária), `benefit_start_date` = nascimento da criança, `benefit_until` = +18 meses (backend), `end_date` não pode exceder o prazo legal. **Base legal**: Lei n.º 26/22 (Lei de Bases da Função Pública) — amamentação art. 93.º n.ºs 2–3 (2 períodos de até 1h/dia, até 18 meses), pré-natais art. 93.º n.º 1, doença art. 90.º (30 dias), necessidades especiais art. 96.º |
| Fluxo | Funcionário cria → `POST {id}/under-review` → Director RH `approve`/`reject` (com nota) → **Despacho PDF** gerado automaticamente |
| Despacho PDF | `DespachoPdfService` (Dompdf em `packages/dompdf`) com cabeçalho Governo, identificação do funcionário (nº agente, gabinete, cargo/categoria), período, motivo, documentos, parecer, decisão APROVADO/REJEITADO e assinatura do Director de RH |
| Assiduidade | dispensa de dia inteiro aplica estado **`dispensado`** na assiduidade (`applyToAttendance`); **não é falta** — `markAbsentForDate` devolve `dispensa_skipped` e cria registo `dispensado` |
| Bloqueios | check-in/check-out/CRUD/`registerAbsence`/`importBiometric`/justificação manual de falta bloqueados para dispensa total aprovada na data (`App\Support\Dispensa`) |
| Amamentação (2h) | `expected_check_out` = entrada real + (horário função pública − 2h); ex.: entrada 08:00 → saída prevista 13:00; `expected_check_in` = `work_start` |
| "Ausente" bloqueia horários | se registo com `status=absent`, não é possível registar entrada/saída (primeiro justificar a falta) |
| `employees-for-point` | funcionários com dispensa total aparecem com `display_name="Nome — Dispensa aprovada"`, `on_dispensa=true`, `blocked=true` e mensagem |
| Expiração 18 meses | comando `rh:expire-breastfeeding-dispensas` (05:00 daily) desactiva o benefício + log `expired` |
| Permissões | módulo `RH Dispensas` (`rh-dispensas-show/create/edit/delete/approve/reject/cancel/underreview/despacho`) — Director RH vê tudo; quem tem apenas `show` vê mas não decide |
| API | `/api/rh/attendance/solicitacoes` (+ `/metadata`, `{id}/approve`, `{id}/reject`, `{id}/under-review`, `{id}/cancel`, `{id}/despacho`, `{id}/despacho/download`, `{id}/documents/{doc}/download`) |
| API Tipos | `/api/rh/attendance/solicitacoes/tipos` (CRUD: `required_documents`, `max_days`, `legal_ref`) — permissões `rh-dispensas-*`; código imutável se existirem solicitações; remoção bloqueada em utilização (recomenda-se `is_active=false`) |

## FLUXO 22 — Relatório Governamental de Pontualidade e Assiduidade ✅
| Item | Descrição |
|------|-----------|
| `AttendanceReportService` | dados com os **mesmos filtros da listagem** (`period`, `date`, `start_date`+`end_date`, `employee_id`) + PDF (Dompdf) em formato Governo (cabeçalho REPÚBLICA DE ANGOLA / GOVERNO DA PROVÍNCIA DO HUAMBO / GABINETE DE RH) |
| Individual | `employee_id` (ou `employee/{employee_id}`) → relatório por funcionário com identificação |
| Resumo | funcionários, registos, dias úteis, presentes, atrasos, faltas justificadas/injustificadas, **dispensas**, total de horas |
| API | `GET /api/rh/attendance/report` (dados), `GET /api/rh/attendance/report/download` (PDF), `GET /api/rh/attendance/report/employee/{id}` e `/download` |
| Anterior | `monthlyReport` (`reports/{employee_id}`) mantém-se por compatibilidade (relatório isolado "do mês" — o frontend deve usar o novo `/report` de filtro completo) |

## Dashboard e Relatórios ✅
| Item | Descrição |
|------|-----------|
| `DashboardService` | overview, monthlyBirthdays, leaveSummary, attendanceSummary, documentExpiryAlert, turnover, salaryEvolutionByDepartment |
| API | `GET /api/v1/rh/dashboard/overview`, `/monthly-birthdays`, `/leave-summary`, `/attendance-summary`, `/document-expiry-alert`, `/turnover`, `/salary-evolution` |

## Notificações (Completas) ✅
| Notificação | Disparo | Canais |
|-------------|---------|--------|
| `LeaveRequestSubmittedNotification` | Submissão de pedido de férias → responsável departamento | mail + database |
| `LeaveRequestApprovedNotification` | Aprovação de férias → funcionário | mail + database |
| `LeaveRequestRejectedNotification` | Rejeição de férias → funcionário | mail + database |
| `ProgressionSubmittedNotification` | Submissão de progressão → responsável departamento | mail + database |
| `ProgressionApprovedNotification` | Aprovação de progressão → funcionário | mail + database |
| `ProgressionRejectedNotification` | Rejeição de progressão → funcionário | mail + database |
| `RetirementProcessNotification` | Actualização de processo de reforma | mail + database |
| `PerformanceEvaluationNotification` | Avaliação pendente/concluída/feedback | mail + database |

## Notificações Agendadas ✅
| Comando | Horário | Descrição |
|---------|---------|-----------|
| `rh:check-birthdays` | 08:00 daily | Notifica aniversariantes do dia |
| `rh:check-document-expiry` | 06:00 daily | Notifica docs a vencer (30 dias) |
| `rh:check-pending-evaluations` | 09:00 semanal (Seg) | Notifica avaliadores sobre avaliações pendentes |
| `rh:check-pending-leaves` | 07:00 daily | Alerta pedidos de férias pendentes > 3 dias |
| `rh:expire-breastfeeding-dispensas` | 05:00 daily | Expira dispensas de amamentação com benefício ultrapassado (18 meses) |

---

## Stack Técnica (em uso)
- **Padrão**: Repository + Service + Controller (AbstractRepository/AbstractService/AbstractController)
- **Validação**: BaseFormRequest por módulo
- **Auth**: Sanctum com JWT
- **Base dados**: MySQL via Docker
- **Notificações**: Laravel Notifications (database + mail)
- **SoftDeletes** em todas as tabelas
- **Transactions** em todas as operações de escrita

### Convenção FormRequest (create vs update)
- **SEMPRE** usar `$this->requiredOnCreate()` em vez de `'required'` para campos que só são obrigatórios na criação
- `BaseFormRequest::requiredOnCreate()` retorna `'required'` no POST, `'sometimes'` no PUT/PATCH
- Isto permite actualizações parciais (só enviar os campos que se quer alterar)
- Exemplo correto:
  ```php
  'name' => [$this->requiredOnCreate(), 'string', 'max:255'],
  'code' => [$this->requiredOnCreate(), 'string', 'max:50', "unique:table,code,{$id},id"],
  ```
- Campos que NUNCA devem usar `requiredOnCreate` (devem ser sempre `nullable`): `status`, `notes`, `description`, campos opcionais
- Campos que devem ser sempre `required` (mesmo na edição): nenhum — se for essencial, o controller deve validar separadamente

### Convenção de Idioma
- **Todas** as mensagens de resposta da API devem estar em **português (PT)**
- Mensagens de erro: `'Erro interno no servidor.'`, `'Recurso não encontrado.'`, `'Erro de validação'`
- Mensagens de sucesso: textos em PT
- Log messages (logToDatabase, Log::error): em PT quando visíveis ao utilizador
- Template `MakeFullModuleCommand`: gerar controllers com mensagens em PT

### Convenção employee_number
- `employee_number` (código do agente) é **cadastrado manualmente** pelo utilizador
- **NÃO** é gerado pelo sistema — o `boot()` do model `Employee` não gera nada
- Obrigatório na criação (`requiredOnCreate`), único, máx. 50 caracteres
- Na edição é `sometimes` — só actualiza se enviado

### Convenção Cargo vs Categoria (Aug 2026 — tabela `categories` própria)
- **`categories`** = quadro de carreiras (Técnico Superior de 1.ª Classe, Assessor…), com `group` (ASSESSOR, TÉCNICO…), `level`, `base_salary`
- **`positions`** = apenas cargos/funções de chefia (`type='cargo'`: Governador, Director, Chefe de Departamento…) — coluna `type` mantém-se com default `'cargo'`; validação só aceita `'cargo'`
- `employees.position_id` = **Cargo** (FK positions; desvincular enviando `null` no PUT)
- `employees.category` = **Categoria** (FK **categories**, opcional; desvinculável por `null`)
- Um funcionário pode ter Cargo=Diretor e Categoria=Técnico Superior simultaneamente
- Migration move as antigas posições `type='categoria'` para `categories` preservando IDs (FKs continuam válidas)
- Seeders: `CategorySeed` (34 categorias do quadro) + `PositionSeed` (7 cargos)
- CRUD categorias: `/api/rh/categories` com permissões `RH Categorias` (`rh-categorias-show/create/edit/delete`)

### Convenção Documentos do Funcionário (Aug 2026)
- Campo `name` (Nome do Documento) **não é aceite da API** — removido dos FormRequests
- Nome determinado pelo sistema: nome do `document_type` se existir; senão nome original do ficheiro
- Datas: `issue_date` = emissão, `expiry_date` = validade (guardadas por chave; swap de datas seria bug do frontend)

### Convenção Route Parameter
- Todas as rotas RH usam `{id}` como parâmetro (ex: `Route::put('{id}', ...)`)
- No FormRequest, **SEMPRE** usar `$this->route('id')` para obter o ID actual
- **NÃO** usar nomes de entidade (`$this->route('department')`, `$this->route('position')`, etc.)
- Se `$id` for null, o `unique:table,column,{$id},id` nunca ignora o registo actual → erro "já está sendo utilizado" na edição
- Exemplo correcto:
  ```php
  $id = $this->route('id');  // CORRECTO
  // $id = $this->route('department');  // ERRADO — retorna null
  ```

---

# Sessão: Correção de Testes RH (Jul 2026)

## Problema
Testes RH tinham ~140 falhas: SQLSTATE column errors, 500 errors, 404/422 assertion failures — causados por fábricas com campos incompatíveis com as migrations, URLs com prefixo errado (`/api/v1/rh` vs `/api/rh`), e violações de unique constraint em testes.

## O que foi feito
### URLs e Rotas
- Substituído `/api/v1/rh` → `/api/rh` em 18 ficheiros de teste
- Corrigidos URLs nos testes LeaveApproval, ProgressionRuleCheckEligibility, UserManagement

### Fábricas (46 fábricas analisadas, 20 corrigidas)
| Fábrica | Correção |
|---------|----------|
| ShiftFactory | `check_in_time`/`check_out_time` → `start_time`/`end_time`; adicionado `duration_hours` |
| AttendanceFactory | `total_hours` → `hours_worked`; `check_in`/`check_out` → formato `H:i:s` |
| ShiftAssignmentFactory | `start_date` → `effective_date`; removido `is_active` |
| DisciplinaryRecordFactory | `incident_date` → `occurred_at`; `recorded_by` → `reported_by` |
| DisciplinaryTypeFactory | `severity` de int para string |
| PayrollPeriodFactory | adicionado `code`; removido `year`/`month` |
| EmployeeBenefitFactory | `end_date` derivado de `start_date` |
| JobOpeningFactory | adicionado `code` |
| TrainingSessionFactory | adicionado `name` |
| TrainingEnrollmentFactory | removido `enrolled_date` (não existe na migration) |
| ApplicationFactory | removido `applied_date` |
| InterviewFactory | `interview_date` → `scheduled_at` |
| CandidateFactory | removido `status` |
| LeavePlanFactory | removido `days_remaining` |
| PayrollItemFactory | `bonuses` → `other_earnings`; `inss` → `inss_deduction`; `irt` → `irt_deduction` |
| PerformanceCycleFactory | removido `description`; `end_date` derivado de `start_date` |
| PerformanceEvaluationFactory | `final_score` → `overall_score`; removido `rating` |
| PerformanceGoalFactory | `target_value`/`actual_value`/`status` → `score`/`category`/`notes` |
| TrainingCertificateFactory | reescrita: `enrollment_id` em vez de `employee_id`/`course_id`/`session_id`; `issued_at` em vez de `issued_date` |
| ProgressionRuleFactory | `min_months_in_position` → `min_months_in_category`; removido `min_level`; adicionado `requires_training`/`requires_evaluation`; removido `unique()` do `name` |
| FunctionalHistoryFactory | `previous_value`/`new_value` agora em `json_encode()` |

### Outras correções
- **Migration logs**: adicionada coluna `alert_id` que faltava
- **AbstractController**: tipo `$service` mudado de `AbstractService` para `mixed`
- **DashboardService**: adicionado `->toArray()` no `salaryEvolutionByDepartment()`
- **Unique constraints**: 5 testes corrigidos (LeavePlan, Payslip, PerformanceEvaluation, EmployeePortal) — removidos overrides que causavam duplicação de chaves compostas

## Resultado
- **Antes**: 292 testes, ~140 falhas
- **Depois**: 292 testes, 0 erros, 15 falhas (apenas falhas de lógica de controller/service)
- Todos os SQLSTATE e OverflowException eliminados

## Falhas Remanescentes (15)
Problemas de lógica nos controllers/serviços, não de fábricas/migrations:
- AttendanceImportLogTest — assertion `false is true`
- AttendanceTest (2) — 500 errors
- ProgressionRequestTest (3) — 500/404
- DashboardTest (1) — 500
- LeaveApprovalTest (2) — 500
- PayslipTest (1) — 200 em vez de 422
- EvaluationScoreTest (2) — 500
- PerformanceEvaluationTest (2) — 500
- EmployeePortalTest (1) — 404 em vez de 403
