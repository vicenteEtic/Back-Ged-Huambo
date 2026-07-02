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
| `career_service_times` | migration com campos `institution_entry_date`, `category`, `career_regime` em employees |
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
| Item | Status |
|------|--------|
| `shifts` | turnos com minutos de tolerância |
| `shift_assignments` | alocação por employee com datas |
| `POST check-in` / `POST check-out` | registo de ponto com cálculo de atrasos e horas extra |
| `POST absence` | registo de falta com justificação |
| `POST import-biometric` | importação de CSV biométrico com logging |
| Relatório mensal | por employee |

## FLUXO 13 — Documentos dos Funcionários ✅
| Item | Status |
|------|--------|
| `employee_documents` | upload, tipo, validade |
| Notificações | `DocumentExpiryNotification` com comando `rh:check-document-expiry` |
| API | `/api/v1/rh/employees/{id}/documents` |

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

## Notificações Agendadas ✅
| Comando | Horário | Descrição |
|---------|---------|-----------|
| `rh:check-birthdays` | 08:00 daily | Notifica aniversariantes do dia |
| `rh:check-document-expiry` | 06:00 daily | Notifica docs a vencer (30 dias) |

---

## Stack Técnica (em uso)
- **Padrão**: Repository + Service + Controller (AbstractRepository/AbstractService/AbstractController)
- **Validação**: BaseFormRequest por módulo
- **Auth**: Sanctum com JWT
- **Base dados**: MySQL via Docker
- **Notificações**: Laravel Notifications (database + mail)
- **SoftDeletes** em todas as tabelas
- **Transactions** em todas as operações de escrita
