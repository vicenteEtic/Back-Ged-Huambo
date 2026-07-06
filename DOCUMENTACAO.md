# Documentação do Projecto — Back-Ged-Huambo

> Sistema de Gestão de Recursos Humanos (RH) com módulo KYT/AML integrado.
> **Stack:** Laravel 11.x / PHP 8.2+ | MySQL | Sanctum + JWT | Horizon (Redis) | Reverb (WebSockets)

---

## Índice

1. [Arquitectura Geral](#1-arquitectura-geral)
2. [Controllers](#2-controllers)
3. [Services](#3-services)
4. [Repositories](#4-repositories)
5. [Models](#5-models)
6. [Form Requests](#6-form-requests)
7. [Notifications](#7-notifications)
8. [Commands](#8-commands)
9. [Jobs](#9-jobs)
10. [API Endpoints](#10-api-endpoints)
11. [Base de Dados](#11-base-de-dados)

---

## 1. Arquitectura Geral

```
Request (FormRequest) → Controller → Service → Repository → Model (Eloquent)
                                                              ↕
                                                           MySQL DB
```

| Camada | Responsabilidade |
|--------|------------------|
| **FormRequest** | Validação e autorização dos dados de entrada |
| **Controller** | Orquestra o fluxo HTTP (request → response) |
| **Service** | Lógica de negócio (regras, cálculos, notificações) |
| **Repository** | Acesso a dados (queries, filtros, paginação) |
| **Model** | Representação Eloquent da tabela |

**Padrão:** `AbstractRepository` → `AbstractService` → `AbstractController` + `BaseFormRequest`
- SoftDeletes em todas as tabelas
- Transactions em todas as operações de escrita
- Sanitização XSS via `strip_tags` + `trim` no AbstractService
- Paginação dinâmica + filtros avançados no AbstractRepository

---

## 2. Controllers

### 2.1 Base

| Classe | Ficheiro | Descrição |
|--------|----------|-----------|
| **`AbstractController`** | `app/Http/Controllers/AbstractController.php` | CRUD genérico: `index()` (listagem paginada/filtrada/ordenada), `show()`, `destroy()` (soft-delete), `restore()`. Todos os controllers RH estendem esta classe. Injeta `AbstractService` e faz logging automático via trait `DatabaseLogger`. |
| **`Controller`** | `app/Http/Controllers/Controller.php` | Base Laravel padrão. Apenas 6 controllers estendem directamente (Career, LeaveApproval, Payslip, Portal, Dashboard). |

### 2.2 Auth (`app/Http/Controllers/Auth/`)

| Classe | Rotas | Descrição |
|--------|-------|-----------|
| **`AuthenticatedSessionController`** | `POST /api/v1/auth/login`, `POST /api/v1/auth/logout` | Login com sessão e logout com invalidação. |
| **`EmailVerificationNotificationController`** | `POST /api/v1/auth/email/verification-notification` | Reenvio de notificação de verificação de email. |
| **`NewPasswordController`** | `POST /api/v1/auth/reset-password` | Definição de nova password com token de reset. |
| **`PasswordResetLinkController`** | `POST /api/v1/auth/forgot-password` | Envio de link de reset de password. |
| **`VerifyEmailController`** | `GET /api/v1/auth/verify-email/{id}/{hash}` | Verificação de email com assinatura. |

### 2.3 User (`app/Http/Controllers/User/`)

| Classe | Rotas Principais | Descrição |
|--------|------------------|-----------|
| **`UserController`** | `POST login`, `POST verify2fa`, `GET me`, `PUT profile`, `PUT changePassword`, `POST regenerate2fa`, `POST logout`, `GET/POST/PUT/DELETE /user` | Gestão completa de utilizadores: autenticação, 2FA, perfil, CRUD. |

### 2.4 Permission (`app/Http/Controllers/Permission/`)

| Classe | Descrição |
|--------|-----------|
| **`PermissionController`** | CRUD de permissões (`GET/POST/PUT/DELETE /api/v1/permission`) |
| **`RoleController`** | CRUD de roles (`GET/POST/PUT/DELETE /api/v1/role`) |
| **`PermissionRoleController`** | Atribuição de permissões a roles |

### 2.5 Log (`app/Http/Controllers/Log/`)

| Classe | Descrição |
|--------|-----------|
| **`LogController`** | Leitura de logs de auditoria (`GET /api/v1/logs`). Read-only, sem logging da request. |

### 2.6 Alert (`app/Http/Controllers/Alert/`)

| Classe | Descrição |
|--------|-----------|
| **`AlertController`** | CRUD de alertas KYT/AML. Listagem, detalhe, actualização de status. |
| `AlertUserController` | Atribuição de utilizadores a alertas. |
| `CommentAlertController` | Comentários em alertas. |
| `GrupoAlertEmailsController` | Grupos de email para envio de alertas. |
| `GrupoTypeController` | Tipos de grupos de alerta. |
| `UserGrupoAlertController` | Utilizadores associados a grupos de alerta. |
| `AlertAttachmentController` | Upload/gestão de ficheiros anexos a alertas. |

### 2.7 RH — Módulo a Módulo

#### Archive (Gestão de Arquivos)

| Controller | Rotas Extra (além de CRUD herdado) | Descrição |
|-----------|-------------------------------------|-----------|
| **`ArchiveCategoryController`** | `GET /tree`, `GET /by-type/{type}` | Categorias hierárquicas de arquivo. `tree()` devolve árvore com children; `byType()` filtra por tipo. |
| **`ArchiveDocumentController`** | `GET /search`, `GET /by-employee/{id}`, `GET /by-category/{id}`, `PATCH /{id}/approve`, `PATCH /{id}/archive`, `GET/POST /{id}/versions`, `GET/POST /{id}/shares`, `DELETE /{id}/shares/{shareId}` | Documentos com metadados, versões, partilhas e aprovação. `search()` suporta filtros: q, type, status, confidentiality, employee_id, category_id, datas. |

#### Attendance (Ponto e Assiduidade)

| Controller | Rotas Extra | Descrição |
|-----------|-------------|-----------|
| **`AttendanceController`** | `POST /check-in`, `POST /check-out`, `POST /absence`, `GET /{employeeId}/monthly-report`, `POST /import-biometric` | Registo de ponto com validação de horário, faltas, relatório mensal e importação de CSV biométrico. |
| **`ShiftController`** | — | CRUD de turnos com minutos de tolerância. |
| **`ShiftAssignmentController`** | `GET /by-employee/{employeeId}` | Alocação de turnos a funcionários com datas de vigência. |

#### Benefit (Benefícios Sociais)

| Controller | Descrição |
|-----------|-----------|
| **`BenefitTypeController`** | CRUD de tipos de benefício (subsidy, medical, social_support, institutional, other). |
| **`EmployeeBenefitController`** | Atribuição de benefícios a funcionários. |
| **`BenefitClaimController`** | Pedidos de benefícios com fluxo de aprovação. |
| **`MedicalAssistanceController`** | Registo e tracking de assistência médica. |

#### Career (Carreira, Progressões e Reforma)

| Controller | Rotas Extra | Descrição |
|-----------|-------------|-----------|
| **`CareerController`** ⚠️ | `GET /{employeeId}`, `GET /` (com filtros status/department_id) | Cálculo de carreira: tempo total serviço, na categoria, no cargo, na instituição. |
| **`ProgressionRuleController`** | `GET /{id}/check-eligibility/{employeeId}` | Regras de elegibilidade para promoções. |
| **`ProgressionRequestController`** | `PATCH /{id}/approve`, `PATCH /{id}/reject`, `POST /{id}/execute`, `GET /by-employee/{employeeId}` | Fluxo completo: submissão → aprovação multi-nível → execução (actualiza employee + histórico). |
| **`RetirementController`** | `GET /{employeeId}/eligibility`, `GET /{employeeId}/history` | Verificação de elegibilidade (idade + contribuições) e workflow de reforma. |

#### Department (Departamentos)

| Controller | Descrição |
|-----------|-----------|
| **`DepartmentController`** | CRUD de departamentos organizacionais (árvore hierárquica com parent_id). |

#### Disciplinary (Ocorrências Disciplinares)

| Controller | Descrição |
|-----------|-----------|
| **`DisciplinaryTypeController`** | CRUD de tipos de ocorrências disciplinares (com gravidade). |
| **`DisciplinaryRecordController`** | Registo de ocorrências (ocorrência → investigação → decisão → arquivo). |

#### Employee (Funcionários)

| Controller | Descrição |
|-----------|-----------|
| **`EmployeeController`** | CRUD completo de funcionários (dados pessoais, profissionais, bancários, documentos, carreira). |

#### EmployeeDocument (Documentos dos Funcionários)

| Controller | Descrição |
|-----------|-----------|
| **`EmployeeDocumentController`** | Upload e gestão de documentos por funcionário (tipo, validade, ficheiro). |

#### FunctionalHistory (Histórico Funcional)

| Controller | Descrição |
|-----------|-----------|
| **`FunctionalHistoryController`** | Registo de alterações funcionais (categoria, cargo, salário, promoções). |

#### Leave (Férias e Licenças)

| Controller | Rotas Extra | Descrição |
|-----------|-------------|-----------|
| **`LeaveTypeController`** | — | CRUD de tipos de licença (férias, médica, luto, casamento, paternidade). |
| **`LeaveRequestController`** | `GET /{employeeId}/balance` | Submissão de pedidos com cálculo automático de dias úteis e saldo de férias. |
| **`LeavePlanController`** | `POST /{id}/sync-balance`, `GET /calendar` | Planeamento anual de férias, sincronização de saldos e calendário (filtro ano/departamento). |
| **`LeaveApprovalController`** ⚠️ | `PATCH /{leaveRequestId}/approve`, `PATCH /{leaveRequestId}/reject`, `GET /pending` | Aprovação/rejeição multi-nível hierárquica com notificações. |

#### Payroll (Folha de Pagamento)

| Controller | Descrição |
|-----------|-----------|
| **`PayrollPeriodController`** | CRUD de períodos de processamento salarial. |
| **`PayrollItemController`** | CRUD de itens salariais por employee (base, subsídios, descontos). |
| **`PayslipController`** ⚠️ | `GET /`, `GET /{id}`, `GET /by-employee/{id}`, `POST /generate/{periodId}` — Geração de títulos de vencimento a partir de payroll_items. |

#### Performance (Avaliação de Desempenho)

| Controller | Rotas Extra | Descrição |
|-----------|-------------|-----------|
| **`PerformanceCycleController`** | — | CRUD de ciclos de avaliação. |
| **`PerformanceGoalController`** | — | CRUD de metas/objectivos por ciclo. |
| **`PerformanceEvaluationController`** | `POST /{id}/calculate` | CRUD de avaliações + cálculo de nota ponderada com classificação (Excelente/Bom/Satisfatório/Suficiente/Insuficiente). |
| **`EvaluationCriterionController`** | — | CRUD de critérios de avaliação com pesos e pontuação máxima. |
| **`EvaluationScoreController`** | `GET /by-evaluation/{evaluationId}` | Pontuações por critério com recálculo automático do score global. |

#### Portal (Portal do Funcionário) ⚠️

| Controller | Rotas | Descrição |
|-----------|-------|-----------|
| **`EmployeePortalController`** | `GET /profile`, `GET /leave-balance`, `GET /salary-history`, `GET /career`, `GET /benefits`, `POST /payslip/{id}/download` | Endpoints read-only scoped ao utilizador autenticado. Autoconsulta de perfil, saldo férias, histórico salarial, carreira, benefícios e download de payslip. |

#### Position (Cargos)

| Controller | Descrição |
|-----------|-----------|
| **`PositionController`** | CRUD de cargos (nível hierárquico, salário base, requisitos). |

#### Recruitment (Recrutamento e Selecção)

| Controller | Descrição |
|-----------|-----------|
| **`JobOpeningController`** | CRUD de vagas de emprego (departamento, cargo, requisitos, status). |
| **`CandidateController`** | CRUD de candidatos (dados pessoais, currículo). |
| **`ApplicationController`** | Candidaturas (ligação candidato-vaga com status). |
| **`InterviewController`** | Entrevistas (agendamento, avaliadores, resultado). |

#### Reports (Dashboard e Relatórios) ⚠️

| Controller | Rotas | Descrição |
|-----------|-------|-----------|
| **`DashboardController`** | `GET /overview`, `GET /monthly-birthdays`, `GET /leave-summary`, `GET /attendance-summary`, `GET /document-expiry-alert`, `GET /turnover`, `GET /salary-evolution` | Métricas e indicadores de RH: visão geral, aniversariantes, resumo férias/ponto, alertas documentos, rotatividade, evolução salarial. |

#### Training (Formação e Desenvolvimento)

| Controller | Descrição |
|-----------|-----------|
| **`TrainingCourseController`** | CRUD de cursos de formação (catálogo). |
| **`TrainingSessionController`** | CRUD de sessões/turmas (datas, formador, local). |
| **`TrainingEnrollmentController`** | Inscrições de funcionários em sessões. |

> ⚠️ = Controller que **não** estende `AbstractController`, estende `Controller` directamente.

---

## 3. Services

### 3.1 Base

| Classe | Descrição |
|--------|-----------|
| **`AbstractService`** | CRUD genérico delegando para `AbstractRepository`: `index()`, `store()`, `show()`, `update()`, `destroy()`, `restore()`, `findOneBy()`, `storeOrUpdate()`. Sanitiza dados com `clean()` (strip_tags + trim). |

### 3.2 RH — Services com Lógica de Negócio (além de CRUD)

#### Career (Carreira)

| Service | Métodos Extra | Descrição |
|---------|--------------|-----------|
| **`CareerService`** ⚠️ | `calculate(Employee)`, `calculateForAll(array filters)` | Calcula tempo total de serviço, na categoria, no cargo, na instituição. Formata: "5 ano(s), 3 mês(es), 10 dia(s)". |
| **`ProgressionRuleService`** | `checkEligibility(Employee, ?ProgressionRule)`, `calculateNewSalary(Employee, ProgressionRule)` | Verifica elegibilidade (categoria, meses mínimos, score mínimo, nível). Calcula novo salário com base no percentual de aumento. |
| **`ProgressionRequestService`** | `submit()`, `approve()`, `reject()`, `execute()` | Fluxo completo: submissão com notificação → aprovação multi-nível → execução (actualiza employee + regista functional history). |
| **`RetirementService`** ⚠️ | `checkEligibility(Employee)`, `processHistory(int employeeId)` | Verifica idade ≥60 + contribuições ≥15 anos. Cria `RetirementEligibility` com data esperada de reforma. |

#### Leave (Férias)

| Service | Métodos Extra | Descrição |
|---------|--------------|-----------|
| **`LeaveRequestService`** | `submit()`, `balanceByEmployee()`, `calculateBusinessDays()` | Submete pedido: calcula dias úteis (seg-sex), cria/liga ao plano anual, sincroniza saldo, notifica responsável. |
| **`LeavePlanService`** | `syncBalance()`, `calendar()` | Recalcula dias usados (approved) e pendentes a partir dos leave_requests. Calendário anual com filtro departamento. |
| **`LeaveApprovalService`** | `approve()`, `reject()` | Aprovação multi-nível sequencial. Aprova/rejeita com notificação, actualiza status. |

#### Attendance (Ponto)

| Service | Métodos Extra | Descrição |
|---------|--------------|-----------|
| **`AttendanceService`** | `registerCheckIn()`, `registerCheckOut()`, `registerAbsence()`, `monthlyReport()`, `importBiometric()`, `resolveShift()` | Check-in: resolve turno, calcula atraso (vs início + tolerância). Check-out: calcula horas trabalhadas e extras. Falta: regista tipo/motivo. Relatório mensal: totais, presenças, atrasos, faltas, minutos extra. Importação biométrica: procura employee por número, cria/actualiza, loga erros. |

#### Performance (Avaliação)

| Service | Métodos Extra | Descrição |
|---------|--------------|-----------|
| **`PerformanceEvaluationService`** | `calculateOverall()`, `getClassification()`, `listByEmployee()` | Calcula nota ponderada: normaliza score (0-100) × peso do critério. Classifica: ≥90 Excelente, ≥75 Bom, ≥60 Satisfatório, ≥40 Suficiente, <40 Insuficiente. |
| **`EvaluationScoreService`** | `calculateOverall()` | Recálculo automático da nota global da avaliação sempre que um score é criado/actualizado. |

#### Payroll

| Service | Métodos Extra | Descrição |
|---------|--------------|-----------|
| **`PayslipService`** ⚠️ | `generateForPeriod(int periodId)`, `historyByEmployee(int employeeId)`, `markDownloaded(int id)` | Gera payslips para todos os payroll_items aprovados de um período. Número sequencial: P{code}-{seq}. Tracking de download. |

#### Reports (Dashboard)

| Service | Métodos Extra | Descrição |
|---------|--------------|-----------|
| **`DashboardService`** ⚠️ | `overview()`, `monthlyBirthdays()`, `leaveSummary()`, `attendanceSummary()`, `documentExpiryAlert()`, `turnover()`, `salaryEvolutionByDepartment()` | Agrega dados: totais, distribuição por departamento/género/contrato, estatísticas salariais, aniversariantes, resumo férias/ponto, alertas documentos, rotatividade anual, evolução salarial. |

> ⚠️ = Não estende `AbstractService`, implementa lógica de domínio específica.

### 3.3 RH — Services CRUD Puro (apenas herdam AbstractService)

| Módulo | Services |
|--------|----------|
| **Archive** | `ArchiveCategoryService`, `ArchiveDocumentService`, `ArchiveDocumentVersionService`, `ArchiveDocumentShareService` |
| **Attendance** | `ShiftService`, `ShiftAssignmentService` |
| **Benefit** | `BenefitTypeService`, `EmployeeBenefitService`, `BenefitClaimService`, `MedicalAssistanceService` |
| **Department** | `DepartmentService` |
| **Disciplinary** | `DisciplinaryTypeService`, `DisciplinaryRecordService` |
| **Employee** | `EmployeeService` |
| **EmployeeDoc** | `EmployeeDocumentService` |
| **FunctionalHist** | `FunctionalHistoryService` |
| **Leave** | `LeaveTypeService` |
| **Payroll** | `PayrollPeriodService`, `PayrollItemService` |
| **Performance** | `PerformanceCycleService`, `PerformanceGoalService`, `EvaluationCriterionService` |
| **Position** | `PositionService` |
| **Recruitment** | `JobOpeningService`, `CandidateService`, `ApplicationService`, `InterviewService` |
| **Training** | `TrainingCourseService`, `TrainingSessionService`, `TrainingEnrollmentService` |

### 3.4 AML / KYT Services

| Classe | Descrição |
|--------|-----------|
| **`KYTService`** | Motor KYT principal. Executa 8 regras AML contra dados de apólices: aumento abrupto de capital, abuso de ciclo de vida, prémio elevado/baixo capital, múltiplas apólices curtas, pagamentos terceiros, alterações frequentes de beneficiários, geografia de risco, reembolso excessivo. Cria alertas e envia emails de grupo. |
| **`CustomerKYTService`** | Segundo motor com 10 regras e scoring mais refinado (Crítico/Alto/Médio/Baixo). Inclui: aumento capital, resgate antecipado, prémio elevado, múltiplas apólices curtas, churning, substituição rápida, pagamentos terceiros, alterações beneficiários, geografia risco, reembolso excessivo. |
| **`CustomerKYTService2`** (KYT21Service) | Versão legada/intermédia com lógica mais simples. |
| **`DynamicKYTService`** | Motor configurável: carrega regras da BD `kyt_rule_definitions` (cache), resolve handlers via mapping (`config('kyt.handlers')`), fallback para `DefaultRuleHandler`. Suporta filtro por tipo de entidade (individual/colective/both). Integra PEP/Sanction checks. |
| **`PepSanctionCheckService`** | Verifica nomes contra APIs externas PEP e Sanctions com cache de 24h. Retorna findings com scores, list IDs, datasets, países. |
| **`CustomerKYTDataMocker`** | Gera dados sintéticos de teste para todos os 11 cenários KYT. Usado pelo comando `kyt:test-random`. |
| **`AmlRuleEngine`** | Motor AML simplificado: 6 regras (AmountLimit, ProductLimit, Frequency, Smurfing, ProfileDeviation, HighRiskCountry). |
| **`AmlAlertService`** | CRUD e processamento de alertas AML. |

#### Handlers KYT (`app/Services/KYT/Rules/`)

| Handler | Função |
|---------|--------|
| `FrequentBeneficiaryChangesHandler` | Detecta alterações frequentes de beneficiários sem justificação |
| `OverpaymentRefundHandler` | Detecta pagamento excessivo seguido de reembolso a terceiro |
| `HighCapitalIncreaseHandler` | Detecta aumentos abruptos de capital em apólices |
| `PolicyLifecycleAbuseHandler` | Identifica cancelamentos/resgates repetidos em curto período |
| `ThirdPartyPaymentHandler` | Sinaliza prémios pagos por terceiros |
| `MultipleShortPoliciesHandler` | Detecta múltiplas apólices de curta duração (smurfing) |
| `HighRiskGeographyHandler` | Sinaliza operações com países de alto risco |
| `DefaultRuleHandler` | Fallback genérico quando não há handler específico |

### 3.5 Outros Services Core

| Classe | Descrição |
|--------|-----------|
| **`EntitiesService`** | CRUD de entidades + importação batch (8000 registos, 10s limite) + estatísticas dashboard |
| **`BeneficialOwnerService`** | Gestão de beneficiários efectivos de entidades |
| **`BeneficialOwnerScoreService`** | Scoring de beneficiários efectivos |
| **`RiskAssessmentService`** | CRUD e cálculos de avaliação de risco |
| **`RiskAssessmentControlService`** | Controlos de avaliação de risco |
| **`RiskFormulaService`** | Gestão de fórmulas de cálculo de risco |
| **`RiskFormulaResolver`** | Resolução/avaliação dinâmica de fórmulas de risco |
| **`RiskHeatMapService`** | Geração de dados para mapa de calor de risco |
| **`RiskScoreCalculator`** | Cálculo de scores de risco compostos |
| **`ProductRiskService`** | Avaliação de risco por produto |
| **`PepService`** | Verificação PEP para entidades |
| **`DiligenceService`** | Due diligence baseada em nível de risco |
| **`AlertService`** | CRUD de alertas + sub-services (AlertUser, CommentAlert, Grupo, etc.) |
| **`AlertAttachmentService`** | CRUD de anexos de alertas |
| **`UserService`** | CRUD + auth (login, 2FA, changePassword, profile) |
| **`PermissionService`** | CRUD de permissões |
| **`RoleService`** | CRUD de roles |
| **`LogService`** | CRUD de logs de auditoria |
| **`IndicatorService`** | CRUD de indicadores |
| **`IndicatorTypeService`** | CRUD de tipos de indicador |
| **`TransationService`** | CRUD + importação batch de transacções |
| **`PoliciesService`** | Serviços relacionados a apólices |
| **`DashboardService`** (AML) | Métricas AML: total entidades, risk assessments, distribuição por tipo (colective/singular), últimas avaliações |

---

## 4. Repositories

### 4.1 Base

| Classe | Descrição |
|--------|-----------|
| **`AbstractRepository`** | CRUD completo com: paginação dinâmica, filtros avançados (`FilterHandlerV2`), ordenação (`FilterHandler`), eager loading, transacções com retry (lock wait timeout), soft-deletes. Métodos: `index()`, `store()`, `storeOrUpdate()`, `firstOrCreate()`, `show()`, `update()`, `destroy()`, `restore()`, `findOneBy()`, `findBy()`, `buildQuery()`. |

### 4.2 Repositories RH (41 ficheiros, 15 módulos)

**Todos seguem o mesmo padrão:** estendem `AbstractRepository`, injectam o Model no construtor, **nenhum adiciona métodos customizados**. Toda a lógica de consulta está no `AbstractRepository`.

| Módulo | Repositories |
|--------|-------------|
| **Archive** | `ArchiveCategoryRepository`, `ArchiveDocumentRepository`, `ArchiveDocumentVersionRepository`, `ArchiveDocumentShareRepository` |
| **Attendance** | `AttendanceRepository`, `ShiftRepository`, `ShiftAssignmentRepository`, `AttendanceImportLogRepository` |
| **Benefit** | `BenefitTypeRepository`, `EmployeeBenefitRepository`, `BenefitClaimRepository`, `MedicalAssistanceRepository` |
| **Career** | `ProgressionRuleRepository`, `ProgressionRequestRepository`, `ProgressionApprovalRepository`, `RetirementProcessRepository` |
| **Department** | `DepartmentRepository` |
| **Disciplinary** | `DisciplinaryTypeRepository`, `DisciplinaryRecordRepository` |
| **Employee** | `EmployeeRepository` |
| **EmployeeDoc** | `EmployeeDocumentRepository` |
| **FunctionalHist** | `FunctionalHistoryRepository` |
| **Leave** | `LeaveTypeRepository`, `LeaveRequestRepository`, `LeavePlanRepository`, `LeaveApprovalRepository` |
| **Payroll** | `PayrollPeriodRepository`, `PayrollItemRepository` |
| **Performance** | `PerformanceCycleRepository`, `PerformanceEvaluationRepository`, `PerformanceGoalRepository`, `EvaluationCriterionRepository`, `EvaluationScoreRepository` |
| **Position** | `PositionRepository` |
| **Recruitment** | `JobOpeningRepository`, `CandidateRepository`, `ApplicationRepository`, `InterviewRepository` |
| **Training** | `TrainingCourseRepository`, `TrainingSessionRepository`, `TrainingEnrollmentRepository` |

### 4.3 Outros Repositories

| Módulo | Repositories |
|--------|-------------|
| **Alert** | `AlertRepository`, `AlertUserRepository`, `CommentAlertRepository`, `GrupoAlertEmailsRepository`, `GrupoTypeRepository`, `UserGrupoAlertRepository` |
| **AlertAttachment** | `AlertAttachmentRepository` |
| **KYT** | `KytRuleRepository`, `kytrulesRepository` |
| **Entity** | `EntitiesRepository` |
| **Permission** | `PermissionRepository`, `RoleRepository`, `PermissionRoleRepository` |
| **User** | `UserRepository` |
| **Log** | `LogRepository` |
| **Indicator** | `IndicatorRepository`, `IndicatorTypeRepository` |

---

## 5. Models

### 5.1 Models RH (SoftDeletes em todos)

#### Foundation

| Model | Tabela | Descrição |
|-------|--------|-----------|
| **`Department`** | `departments` | Departamento organizacional hierárquico (parent_id). Responsável (responsible_id). |
| **`Position`** | `positions` | Cargo/função ligado a departamento. Nível hierárquico, salário base, requisitos. |
| **`Employee`** | `employees` | Funcionário: dados pessoais, profissionais, bancários, carreira (institution_entry_date, category, career_regime). FK para User, Department, Position. |
| **`EmployeeDocument`** | `employee_documents` | Documento por funcionário (tipo, nome, ficheiro, validade, verificado). |
| **`FunctionalHistory`** | `functional_history` | Registo de alterações (tipo, valor anterior/novo, data efeito, criado por). |

#### Attendance

| Model | Tabela | Descrição |
|-------|--------|-----------|
| **`Attendance`** | `attendance` | Registo diário de ponto: check_in/out, horas esperadas/trabalhadas, minutos atraso/extra, falta (tipo, justificada). |
| **`Shift`** | `shifts` | Turno: hora início/fim, minutos tolerância, duração. |
| **`ShiftAssignment`** | `shift_assignments` | Atribuição de turno a employee com período de vigência. |
| **`AttendanceImportLog`** | `attendance_import_logs` | Log de importação CSV (total linhas, importadas, falhadas, erros). |

#### Leave

| Model | Tabela | Descrição |
|-------|--------|-----------|
| **`LeaveType`** | `leave_types` | Tipo de licença: dias padrão, regras de carryover, activo. |
| **`LeaveRequest`** | `leave_requests` | Pedido de férias: datas, total dias, status, aprovações multi-nível. |
| **`LeavePlan`** | `leave_plans` | Planeamento anual: dias concedidos, usados, pendentes, restantes. |
| **`LeaveApproval`** | `leave_approvals` | Aprovação multi-nível: nível, status, comentário, data decisão. |

#### Payroll

| Model | Tabela | Descrição |
|-------|--------|-----------|
| **`PayrollPeriod`** | `payroll_periods` | Período salarial: data início/fim/pagamento, status. |
| **`PayrollItem`** | `payroll_items` | Item salarial por employee: base, subsídios, horas extra, descontos (INSS, IRT), bruto/líquido. |
| **`Payslip`** | `payslips` | Título de vencimento gerado: breakdown completo, tracking (generated_at, downloaded_at). |

#### Performance

| Model | Tabela | Descrição |
|-------|--------|-----------|
| **`PerformanceCycle`** | `performance_cycles` | Ciclo de avaliação: nome, código, datas, status. |
| **`PerformanceEvaluation`** | `performance_evaluations` | Avaliação: score global, pontos fortes, melhorias, status. FK para cycle, employee, evaluator. |
| **`PerformanceGoal`** | `performance_goals` | Meta/objectivo: título, categoria, peso, score. |
| **`EvaluationCriterion`** | `evaluation_criteria` | Critério de avaliação: nome, secção, peso, score máximo. |
| **`EvaluationScore`** | `evaluation_scores` | Pontuação por critério: score, comentário. |

#### Career

| Model | Tabela | Descrição |
|-------|--------|-----------|
| **`ProgressionRule`** | `progression_rules` | Regra de elegibilidade: meses mínimos, score mínimo, requer formação, from/to categoria/nível, % aumento. |
| **`ProgressionRequest`** | `progression_requests` | Pedido de progressão: from/to categoria/cargo, alteração salarial, status. |
| **`ProgressionApproval`** | `progression_approvals` | Aprovação de progressão: nível, status, comentário. |
| **`RetirementEligibility`** | `retirement_eligibility` | Elegibilidade: idade reforma, anos contribuição, data esperada. |
| **`RetirementProcess`** | `retirement_processes` | Processo de reforma: datas, status, salário final, pensão (valor/tipo), documentos. |
| **`PostRetirementHistory`** | `post_retirement_history` | Histórico pós-reforma: tipo, descrição, montante. |

#### Benefit

| Model | Tabela | Descrição |
|-------|--------|-----------|
| **`BenefitType`** | `benefit_types` | Tipo de benefício: categoria (subsidy, medical, social_support, institutional, other), provider, montante, frequência. |
| **`EmployeeBenefit`** | `employee_benefits` | Benefício atribuído: montante, datas, status. |
| **`BenefitClaim`** | `benefit_claims` | Pedido de benefício: montante pedido/aprovado, status, aprovação. |
| **`MedicalAssistance`** | `medical_assistance` | Assistência médica: tipo, provedor, montante, data. |

#### Disciplinary

| Model | Tabela | Descrição |
|-------|--------|-----------|
| **`DisciplinaryType`** | `disciplinary_types` | Tipo disciplinar: nome, código, gravidade. |
| **`DisciplinaryRecord`** | `disciplinary_records` | Registo disciplinar: data ocorrência, evidência, status, resolução, período sanção. |

#### Recruitment

| Model | Tabela | Descrição |
|-------|--------|-----------|
| **`JobOpening`** | `job_openings` | Vaga: título, departamento, cargo, requisitos, número vagas, status, datas. |
| **`Candidate`** | `candidates` | Candidato: nome, email, telefone, currículo, origem. |
| **`Application`** | `applications` | Candidatura: status, carta apresentação. FK para candidate + job_opening. |
| **`Interview`** | `interviews` | Entrevista: data, tipo, classificação, feedback, status. FK para application + evaluators. |

#### Training

| Model | Tabela | Descrição |
|-------|--------|-----------|
| **`TrainingCourse`** | `training_courses` | Curso: nome, código, duração, provedor, categoria. |
| **`TrainingSession`** | `training_sessions` | Sessão: datas, local, formador, max participantes. |
| **`TrainingEnrollment`** | `training_enrollments` | Inscrição: status, nota. |
| **`TrainingCertificate`** | `training_certificates` | Certificado: número, datas emissão/validade, ficheiro. |

#### Archive

| Model | Tabela | Descrição |
|-------|--------|-----------|
| **`ArchiveCategory`** | `archive_categories` | Categoria hierárquica: tipo (processo_individual, administrativo, relatorio, avaliacao, despacho). |
| **`ArchiveDocument`** | `archive_documents` | Documento arquivado: título, número, confidencialidade, tags, ficheiro, metadados, aprovação. |
| **`ArchiveDocumentVersion`** | `archive_document_versions` | Versão: número, ficheiro. |
| **`ArchiveDocumentShare`** | `archive_document_shares` | Partilha: permissões (view/download/edit), validade. FK para user/employee. |

### 5.2 Core Models

| Model | Tabela | Descrição |
|-------|--------|-----------|
| **`User\User`** | `users` | Utilizador: Sanctum + JWT + Fortify (2FA, email verification). Roles via `role_id`. Métodos: `can()`, `cans()`, `hasPermissions()`. |
| **`Permission\Role`** | `roles` | Role: nome, descrição, activo. Many-to-many com Permission via `permission_role`. |
| **`Permission\Permission`** | `permissions` | Permissão: nome, descrição, activo. |
| **`Alert\Alert`** | `alert` | Alerta KYT/AML: entidade (FK), tipo, nível, score, descrição, categoria, atribuído a, PEP/sanction flags. |
| **`AlertAttachment\AlertAttachment`** | `alert_attachment` | Anexo de alerta: nome, ficheiro. |
| **`Log\Log`** | `log` | Log auditoria: nível, IP, path, user, tipo, mensagem, entidade. |

---

## 6. Form Requests

### 6.1 Base

| Classe | Descrição |
|--------|-----------|
| **`BaseFormRequest`** | Extende `FormRequest`. Sobrescreve `failedValidation()` para retornar JSON 422 com erros de validação. Todas as FormRequest estendem esta classe. |

### 6.2 Request Classes

| Módulo | Ficheiros | Valida |
|--------|-----------|--------|
| **Auth** | `LoginRequest` | Login (email, password) |
| **User** | `UserRequest`, `ChangePasswordRequest`, `Verify2faRequest`, `AuthRequest` | CRUD users, password change, 2FA |
| **Permission** | `PermissionRequest`, `RoleRequest` | CRUD permissions/roles |
| **Alert** | `AlertDocumentRequest`, `AlertUpdateStatusRequest` | Documentos e status de alertas |
| **AlertAttachment** | `AlertAttachmentRequest` | Anexos de alertas |
| **RH Archive** | `ArchiveCategoryRequest`, `ArchiveDocumentRequest`, `ArchiveDocumentShareRequest`, `ArchiveDocumentVersionRequest` | CRUD archive |
| **RH Attendance** | `AttendanceRequest`, `ShiftRequest`, `ShiftAssignmentRequest` | CRUD ponto |
| **RH Benefit** | `BenefitTypeRequest`, `EmployeeBenefitRequest`, `BenefitClaimRequest`, `MedicalAssistanceRequest` | CRUD benefícios |
| **RH Career** | `ProgressionRuleRequest`, `ProgressionRequestRequest`, `RetirementProcessRequest` | CRUD carreira/progressão/reforma |
| **RH Department** | `DepartmentRequest` | CRUD departamentos |
| **RH Disciplinary** | `DisciplinaryTypeRequest`, `DisciplinaryRecordRequest` | CRUD disciplinar |
| **RH Employee** | `EmployeeRequest` | CRUD funcionários |
| **RH EmployeeDoc** | `EmployeeDocumentRequest` | CRUD documentos |
| **RH FunctionalHist** | `FunctionalHistoryRequest` | CRUD histórico funcional |
| **RH Leave** | `LeaveTypeRequest`, `LeaveRequestForm`, `LeavePlanRequest` | CRUD férias |
| **RH Payroll** | `PayrollPeriodRequest`, `PayrollItemRequest` | CRUD payroll |
| **RH Performance** | `PerformanceCycleRequest`, `PerformanceGoalRequest`, `PerformanceEvaluationRequest`, `EvaluationCriterionRequest`, `EvaluationScoreRequest` | CRUD performance |
| **RH Position** | `PositionRequest` | CRUD cargos |
| **RH Recruitment** | `JobOpeningRequest`, `CandidateRequest`, `ApplicationRequest`, `InterviewRequest` | CRUD recrutamento |
| **RH Training** | `TrainingCourseRequest`, `TrainingSessionRequest`, `TrainingEnrollmentRequest` | CRUD formação |

---

## 7. Notifications

Todas usam canais **mail + database**.

### 7.1 RH Notifications

| Notificação | Disparada Por | Quando | Para Quem | Conteúdo |
|-------------|--------------|--------|-----------|----------|
| **`BirthdayNotification`** | `rh:check-birthdays` (08:00 daily) | Dia do aniversário | Funcionário | Parabéns com employee_id e full_name |
| **`DocumentExpiryNotification`** | `rh:check-document-expiry` (06:00 daily) | Documento a ≤30 dias expirar | Funcionário | Alerta com dias restantes |
| **`LeaveRequestSubmittedNotification`** | `LeaveRequestService::submit()` | Novo pedido | Responsável departamento | Datas e tipo de licença |
| **`LeaveRequestApprovedNotification`** | `LeaveApprovalService::approve()` | Aprovação | Funcionário que solicitou | Confirmação com datas |
| **`LeaveRequestRejectedNotification`** | `LeaveApprovalService::reject()` | Rejeição | Funcionário que solicitou | Motivo da rejeição |
| **`ProgressionSubmittedNotification`** | `ProgressionRequestService::submit()` | Novo pedido | Responsável departamento | Dados da progressão |
| **`ProgressionApprovedNotification`** | `ProgressionRequestService::approve()` | Aprovação | Funcionário | Confirmação |
| **`ProgressionRejectedNotification`** | `ProgressionRequestService::reject()` | Rejeição | Funcionário | Motivo da rejeição |
| **`RetirementProcessNotification`** | Workflow reforma | Mudança status | Funcionário | Actualização do processo (draft/approved/concluded) |
| **`PerformanceEvaluationNotification`** | `rh:check-pending-evaluations` (Semanal) | Pendente/concluída | Avaliador/funcionário | Evento do ciclo de avaliação |

### 7.2 Core Notification

| Notificação | Descrição |
|-------------|-----------|
| **`ResetPasswordNotification`** | Envio de link de reset de password com frontend URL de `config('app.frontend_url')`. Usa markdown `emails.auth.reset-password`. |

---

## 8. Commands

### 8.1 RH Scheduled Commands

| Comando | Assinatura | Agendamento | Descrição |
|---------|-----------|-------------|-----------|
| **`CheckBirthdaysCommand`** | `rh:check-birthdays` | 08:00 daily | Encontra funcionários activos com aniversário hoje e envia `BirthdayNotification` |
| **`CheckDocumentExpiryCommand`** | `rh:check-document-expiry {--days=30}` | 06:00 daily | Encontra documentos não verificados a expirar em N dias e envia `DocumentExpiryNotification` |
| **`CheckPendingEvaluationsCommand`** | `rh:check-pending-evaluations` | 09:00 weekly (Mon) | Encontra avaliações pendentes e envia `PerformanceEvaluationNotification` |
| **`CheckPendingLeavesCommand`** | `rh:check-pending-leaves` | 07:00 daily | Reporta pedidos de férias pendentes há >3 dias (output console para gestores) |

### 8.2 Core Commands

| Comando | Assinatura | Descrição |
|---------|-----------|-----------|
| **`CreateAdminUserCommand`** | `user:create-interactive` | Criação/actualização interactiva de utilizador admin (selecciona Role, define nome/email/telefone/password) |
| **`MakeFullModuleCommand`** | `make:module {name} {--m} {--r} {--s} {--c} {--f} {--all}` | Gerador automático de módulo: Model + Repository + Service + Controller + FormRequest. Suporta subdirectórios. |
| **`TestKYTEngine`** | `kyt:test-random` | Testa motor KYT com 10 entidades aleatórias, executa todos os 11 cenários usando `CustomerKYTDataMocker` |
| **`UpdateAlertPepCommand`** | `app:update-alert-pep-command` | Dispara `AlertJob` para processar todas entidades contra listas PEP/Sanctions |
| **`ImportDataCommand`** | `import:data {--sync}` | Dispara importação de clientes e apólices (sync ou queue) |

---

## 9. Jobs

| Job | Fila | Descrição |
|-----|------|-----------|
| **`AlertJob`** | default | Processa todas as entidades e beneficiários efectivos contra API PEP e Sanction externas. Cria/actualiza alertas. |
| **`GenerateAlertsJob`** | default | Gera alertas para uma entidade específica com base num risk assessment string. Cria alertas gerais, PEP e Sanctions. |
| **`SendGrupoAlertEmailJob`** | high | Enviado pelos serviços KYT quando um alerta é criado. Resolve o tipo de alerta, encontra grupos de utilizadores e envia email para todos os membros. Retry até 5 tentativas. |

---

## 10. API Endpoints

**Prefixo:** `/api/v1/`  
**Auth:** `auth:sanctum` (excepto auth público)  
**Formato resposta:** JSON

### 10.1 Auth (Público)

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/auth/login` | Login |
| POST | `/auth/2fa` | Verificar código 2FA |
| POST | `/auth/forgot-password` | Solicitar reset de password |
| POST | `/auth/reset-password` | Definir nova password |
| GET | `/auth/verify-email/{id}/{hash}` | Verificar email |
| POST | `/auth/email/verification-notification` | Reenviar verificação email |
| POST | `/auth/logout` | Logout |

### 10.2 Users & Permissions

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET/POST/PUT/DELETE | `/permission` | CRUD permissões |
| GET/POST/PUT/DELETE | `/role` | CRUD roles |
| GET | `/user/me` | Perfil do utilizador actual |
| GET/POST/PUT | `/user` | CRUD utilizadores |
| PUT | `/user/changePassword/{id}` | Alterar password |
| POST | `/user/logout` | Logout |

### 10.3 RH — Departments

| Método | Endpoint |
|--------|----------|
| GET/POST | `/rh/departments` |
| GET/PUT/DELETE | `/rh/departments/{id}` |

### 10.4 RH — Positions

| Método | Endpoint |
|--------|----------|
| GET/POST | `/rh/positions` |
| GET/PUT/DELETE | `/rh/positions/{id}` |

### 10.5 RH — Employees

| Método | Endpoint |
|--------|----------|
| GET/POST | `/rh/employees` |
| GET/PUT/DELETE | `/rh/employees/{id}` |

### 10.6 RH — Employee Documents

| Método | Endpoint |
|--------|----------|
| GET/POST | `/rh/employees/{employee_id}/documents` |
| GET/PUT/DELETE | `/rh/employees/{employee_id}/documents/{id}` |

### 10.7 RH — Leaves

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET/POST/PUT/DELETE | `/rh/leaves/leave-types` | Tipos de licença |
| GET/POST/PUT/DELETE | `/rh/leaves/leave-requests` | Pedidos de férias |
| GET | `/rh/leaves/leave-requests/{id}/balance` | Saldo de férias |
| GET/POST/PUT/DELETE | `/rh/leaves/plans` | Planos anuais |
| POST | `/rh/leaves/plans/{id}/sync-balance` | Sincronizar saldo |
| GET | `/rh/leaves/approvals/pending` | Aprovações pendentes |
| POST | `/rh/leaves/approvals/{leave_request_id}/approve` | Aprovar pedido |
| POST | `/rh/leaves/approvals/{leave_request_id}/reject` | Rejeitar pedido |
| GET | `/rh/leaves/calendar` | Calendário de férias |

### 10.8 RH — Attendance

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET/POST/PUT/DELETE | `/rh/attendance/shifts` | Turnos |
| GET/POST/PUT/DELETE | `/rh/attendance/assignments` | Atribuições de turno |
| GET/POST/PUT/DELETE | `/rh/attendance/records` | Registos de ponto |
| POST | `/rh/attendance/check-in` | Registar entrada |
| POST | `/rh/attendance/check-out` | Registar saída |
| POST | `/rh/attendance/absence` | Registar falta |
| POST | `/rh/attendance/import-biometric` | Importar CSV biométrico |
| GET | `/rh/attendance/reports/{employee_id}` | Relatório mensal |

### 10.9 RH — Payroll & Payslips

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET/POST/PUT/DELETE | `/rh/payroll/periods` | Períodos salariais |
| GET/POST/PUT/DELETE | `/rh/payroll/items` | Itens salariais |
| GET | `/rh/payslips` | Listar payslips |
| GET | `/rh/payslips/{id}` | Detalhe payslip |
| GET | `/rh/payslips/by-employee/{employee_id}` | Histórico por employee |
| POST | `/rh/payslips/generate/{period_id}` | Gerar payslips |

### 10.10 RH — Recruitment

| Método | Endpoint |
|--------|----------|
| GET/POST/PUT/DELETE | `/rh/recruitment/job-openings` |
| GET/POST/PUT/DELETE | `/rh/recruitment/candidates` |
| GET/POST/PUT/DELETE | `/rh/recruitment/applications` |
| GET/POST/PUT/DELETE | `/rh/recruitment/interviews` |

### 10.11 RH — Training

| Método | Endpoint |
|--------|----------|
| GET/POST/PUT/DELETE | `/rh/training/courses` |
| GET/POST/PUT/DELETE | `/rh/training/sessions` |
| GET/POST/PUT/DELETE | `/rh/training/enrollments` |

### 10.12 RH — Performance

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET/POST/PUT/DELETE | `/rh/performance/cycles` | Ciclos |
| GET/POST/PUT/DELETE | `/rh/performance/goals` | Metas |
| GET/POST/PUT/DELETE | `/rh/performance/evaluations` | Avaliações |
| GET | `/rh/performance/evaluations/{id}/scores` | Scores da avaliação |
| POST | `/rh/performance/evaluations/{id}/calculate` | Calcular nota |
| GET/POST/PUT/DELETE | `/rh/performance/criteria` | Critérios |
| GET/POST/PUT/DELETE | `/rh/performance/scores` | Pontuações |

### 10.13 RH — Benefits

| Método | Endpoint |
|--------|----------|
| GET/POST/PUT/DELETE | `/rh/benefits/types` |
| GET/POST/PUT/DELETE | `/rh/benefits/employee-benefits` |
| GET/POST/PUT/DELETE | `/rh/benefits/claims` |
| GET/POST/PUT/DELETE | `/rh/benefits/medical` |

### 10.14 RH — Disciplinary

| Método | Endpoint |
|--------|----------|
| GET/POST/PUT/DELETE | `/rh/disciplinary/types` |
| GET/POST/PUT/DELETE | `/rh/disciplinary/records` |

### 10.15 RH — Functional History

| Método | Endpoint |
|--------|----------|
| GET/POST | `/rh/functional-history` |
| GET/PUT/DELETE | `/rh/functional-history/{id}` |

### 10.16 RH — Career

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/rh/career` | Carreiras de todos funcionários |
| GET | `/rh/career/{employee_id}` | Carreira de um funcionário |

### 10.17 RH — Progression

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET/POST/PUT/DELETE | `/rh/progression/rules` | Regras |
| GET | `/rh/progression/rules/{id}/check-eligibility/{employee_id}` | Verificar elegibilidade |
| GET/POST/PUT/DELETE | `/rh/progression/requests` | Pedidos |
| POST | `/rh/progression/requests/{id}/approve` | Aprovar |
| POST | `/rh/progression/requests/{id}/reject` | Rejeitar |
| POST | `/rh/progression/requests/{id}/execute` | Executar |

### 10.18 RH — Retirement

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/rh/retirement/eligibility/{employee_id}` | Verificar elegibilidade |
| GET/POST | `/rh/retirement/processes` | Listar/criar processos |
| GET/PUT/DELETE | `/rh/retirement/processes/{id}` | CRUD processo |
| GET | `/rh/retirement/processes/by-employee/{employee_id}` | Processos por employee |

### 10.19 RH — Employee Portal (Self-Service)

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/rh/portal/profile` | Perfil do funcionário |
| GET | `/rh/portal/leave-balance` | Saldo de férias |
| GET | `/rh/portal/salary-history` | Histórico salarial |
| GET | `/rh/portal/career` | Dados de carreira |
| GET | `/rh/portal/benefits` | Benefícios activos |
| POST | `/rh/portal/payslip/{id}/download` | Download de payslip |

### 10.20 RH — Archive

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET/POST/PUT/DELETE | `/rh/archive/categories` | Categorias |
| GET | `/rh/archive/categories/tree` | Árvore de categorias |
| GET | `/rh/archive/categories/by-type/{type}` | Categorias por tipo |
| GET/POST/PUT/DELETE | `/rh/archive/documents` | Documentos |
| GET | `/rh/archive/documents/search` | Pesquisa avançada |
| GET | `/rh/archive/documents/by-employee/{employee_id}` | Docs por employee |
| GET | `/rh/archive/documents/by-category/{category_id}` | Docs por categoria |
| POST | `/rh/archive/documents/{id}/approve` | Aprovar documento |
| POST | `/rh/archive/documents/{id}/archive` | Arquivar documento |
| GET/POST | `/rh/archive/documents/{id}/versions` | Versões |
| GET/POST | `/rh/archive/documents/{id}/shares` | Partilhas |
| DELETE | `/rh/archive/documents/{id}/shares/{share_id}` | Remover partilha |

### 10.21 RH — Dashboard

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/rh/dashboard/overview` | Visão geral |
| GET | `/rh/dashboard/monthly-birthdays` | Aniversariantes do mês |
| GET | `/rh/dashboard/leave-summary` | Resumo de férias |
| GET | `/rh/dashboard/attendance-summary` | Resumo de ponto |
| GET | `/rh/dashboard/document-expiry-alert` | Alertas documentos |
| GET | `/rh/dashboard/turnover` | Taxa de rotatividade |
| GET | `/rh/dashboard/salary-evolution` | Evolução salarial |

### 10.22 Alerts

| Método | Endpoint |
|--------|----------|
| GET | `/alert` |
| PUT | `/alert/{id}/status` |
| GET/POST/PUT/DELETE | `/alert/grupoAlertEmails` |
| GET/POST/PUT/DELETE | `/alert/grupoType` |
| GET/POST/PUT | `/alert/userGrupo` |
| GET/POST/PUT | `/alert/user` |
| GET/POST | `/alert/comment` |
| GET/POST | `/alert/files/{id}` |

### 10.23 Logs

| Método | Endpoint |
|--------|----------|
| GET | `/logs` |

---

## 11. Base de Dados

### 11.1 Core Tables

| Tabela | Descrição |
|--------|-----------|
| `users` | Utilizadores com auth, 2FA, role_id |
| `roles` | Definições de roles |
| `permissions` | Definições de permissões |
| `permission_role` | Pivot role-permissão |
| `notifications` | Notificações em base de dados (morphs) |
| `logs` | Log de auditoria |
| `jobs`, `job_batches`, `failed_jobs` | Queue Horizon |
| `cache`, `cache_locks` | Cache |
| `sessions` | Sessões |

### 11.2 Alert Tables

| Tabela | Descrição |
|--------|-----------|
| `alert` | Alertas KYT/AML |
| `alert_user` | Utilizadores associados a alertas |
| `comment_alert` | Comentários em alertas |
| `grupo_alert_emails` | Grupos de email para alertas |
| `grupo_type` | Tipos de grupos de alerta |
| `user_grupe_alert` | Utilizadores em grupos de alerta |
| `alert_attachment` | Ficheiros anexos a alertas |

### 11.3 KYT / AML Tables

| Tabela | Descrição |
|--------|-----------|
| `kyt_rule_definitions` | Definições de regras KYT dinâmicas |
| `kyt_rule_definition_products` | Produtos associados a regras KYT |
| `entities` | Entidades/clientes |
| `aml_alerts` | Alertas AML |
| `risk_assessment` | Avaliações de risco |
| `policies_staging` | Staging de apólices |
| `recibos_cobrados` | Recibos |
| `policy_changes_staging` | Alterações de apólices |
| `beneficiarios_staging` | Beneficiários |
| `apol_anulada_estorno` | Apólices anuladas/estornadas |

### 11.4 RH Tables

#### Foundation
| Tabela | Descrição |
|--------|-----------|
| `departments` | Departamentos organizacionais (hierárquico) |
| `positions` | Cargos ligados a departamentos |
| `employees` | Funcionários (FK user, department, position) |
| `functional_history` | Histórico de alterações funcionais |
| `employee_documents` | Documentos por funcionário |

#### Attendance
| Tabela | Descrição |
|--------|-----------|
| `attendance` | Registos diários de ponto |
| `shifts` | Turnos de trabalho |
| `shift_assignments` | Atribuição de turnos a employees |
| `attendance_import_logs` | Logs de importação biométrica |

#### Leave
| Tabela | Descrição |
|--------|-----------|
| `leave_types` | Tipos de licença |
| `leave_requests` | Pedidos de férias |
| `leave_plans` | Planeamento anual |
| `leave_approvals` | Aprovações multi-nível |

#### Payroll
| Tabela | Descrição |
|--------|-----------|
| `payroll_periods` | Períodos salariais |
| `payroll_items` | Itens de processamento salarial |
| `payslips` | Títulos de vencimento |

#### Performance
| Tabela | Descrição |
|--------|-----------|
| `performance_cycles` | Ciclos de avaliação |
| `performance_goals` | Metas/objectivos |
| `performance_evaluations` | Avaliações de desempenho |
| `evaluation_criteria` | Critérios de avaliação |
| `evaluation_scores` | Pontuações por critério |

#### Career & Progression
| Tabela | Descrição |
|--------|-----------|
| `progression_rules` | Regras de elegibilidade |
| `progression_requests` | Pedidos de progressão |
| `progression_approvals` | Aprovações de progressão |
| `retirement_eligibility` | Elegibilidade para reforma |
| `retirement_processes` | Processos de reforma |
| `post_retirement_history` | Histórico pós-reforma |

#### Benefits
| Tabela | Descrição |
|--------|-----------|
| `benefit_types` | Tipos de benefício |
| `employee_benefits` | Benefícios atribuídos |
| `benefit_claims` | Pedidos de benefícios |
| `medical_assistance` | Assistência médica |

#### Disciplinary
| Tabela | Descrição |
|--------|-----------|
| `disciplinary_types` | Tipos disciplinares |
| `disciplinary_records` | Registos disciplinares |

#### Recruitment
| Tabela | Descrição |
|--------|-----------|
| `job_openings` | Vagas de emprego |
| `candidates` | Candidatos |
| `applications` | Candidaturas |
| `interviews` | Entrevistas |

#### Training
| Tabela | Descrição |
|--------|-----------|
| `training_courses` | Cursos de formação |
| `training_sessions` | Sessões/turmas |
| `training_enrollments` | Inscrições |
| `training_certificates` | Certificados |

#### Archive
| Tabela | Descrição |
|--------|-----------|
| `archive_categories` | Categorias de arquivo (hierárquico) |
| `archive_documents` | Documentos arquivados |
| `archive_document_versions` | Versões de documentos |
| `archive_document_shares` | Partilhas de documentos |

---

> **Legenda:** ⚠️ = Não segue o padrão Abstract (extende Controller/Service directamente)
> **Documentação gerada em:** Julho 2026
