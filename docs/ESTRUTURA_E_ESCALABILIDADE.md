# Arquitetura do Projecto GED Huambo — Vantagens e Escalabilidade

> **Data:** Julho 2026
> **Stack:** Laravel 11.x | PHP 8.2+ | MySQL 8.0 | Redis | Horizon | Reverb (WebSockets)
> **Repositório:** `vicenteEtic/Back-Ged-Huambo`

---

## 1. Porquê esta Arquitetura?

### 1.1 Separação Clara de Responsabilidades (Layered Architecture)

```
Request (FormRequest) → Controller → Service → Repository → Model (Eloquent)
                                                                  ↕
                                                               MySQL DB
```

Cada camada tem um papel bem definido e imutável:

| Camada | O que faz | O que **não** faz |
|--------|-----------|-------------------|
| **FormRequest** | Valida dados de entrada | Não acede à BD |
| **Controller** | Orquestra request → response | Não contém lógica de negócio |
| **Service** | Regras de negócio, cálculos, notificações | Não acede directamente à BD |
| **Repository** | Queries, filtros, paginação | Não contém regras de negócio |
| **Model** | Representação Eloquent da tabela | Não contém lógica de domínio |

**Porquê é melhor que Controllers monolíticos:**
- Testabilidade: cada camada testa-se isoladamente
- Manutenção: localizar e corrigir bugs é mais rápido
- Reutilização: o mesmo Service pode ser chamado por Controllers, Commands, Jobs
- Substituição de BD: muda-se apenas o Repository, o resto fica intacto

### 1.2 CRUD Genérico com Abstract Classes

Todas as operações CRUD padrão vivem em classes abstractas:

- `AbstractRepository` — `index()`, `store()`, `show()`, `update()`, `destroy()`, `restore()`, `findOneBy()`, `findBy()`
- `AbstractService` — delega para o Repository + sanitização XSS (`strip_tags` + `trim`)
- `AbstractController` — endpoints REST genéricos com logging automático
- `BaseFormRequest` — resposta JSON 422 consistente em caso de erro

**Impacto:** Criação de um novo módulo CRUD = ~5 minutos (comando `php artisan make:module Nome --all`).

### 1.3 Modularização por Domínio

O código está organizado por domínios de negócio, não por tipo técnico:

```
app/
├── Http/Controllers/RH/    ← 18 controllers RH
├── Services/RH/             ← 38 services RH
├── Repositories/RH/         ← 41 repositories RH
├── Models/                  ← models (RH + Core)
├── Http/Controllers/Auth/
├── Services/KYT/
├── Services/Alert/
└── ...
```

**Rotas** também são ficheiros separados por módulo (`routes/rh/*.php`).

**Vantagem:** 5 developers podem trabalhar em 5 módulos diferentes sem conflitos de merge.

### 1.4 SoftDeletes + Transactions + Logging (Cross-Cutting)

Todas as tabelas usam `SoftDeletes`. Todas as operações de escrita correm dentro de `DB::transaction()`. Todas as operações são logadas automaticamente pelo `AbstractController`.

Isto significa que:
- Nenhum registo é apagado definitivamente (auditoria forense)
- Nenhuma operação fica a meio (rollback automático em caso de falha)
- Toda a alteração via API fica registada com user, IP, timestamp

---

## 2. O que o Projecto Já Cobre (Funcionalidades Implementadas)

### 2.1 Core
- Autenticação com Sanctum + JWT + 2FA
- Roles e Permissions (RBAC dinâmico)
- Logs de auditoria
- Notificações (database + mail)
- Jobs assíncronos com Horizon (Redis)

### 2.2 RH (18 módulos)
- Departamentos (hierárquico)
- Cargos (com níveis e salário base)
- Funcionários (completos: dados pessoais, profissionais, bancários, carreira)
- Férias e Licenças (pedido → aprovação multi-nível → calendário)
- Ponto e Assiduidade (check-in/out, turnos, faltas, importação biométrica)
- Documentos dos Funcionários (upload, validade, alertas de expiração)
- Avaliação de Desempenho (critérios, scores ponderados, classificações)
- Progressões e Promoções (regras de elegibilidade → pedido → aprovação → execução)
- Carreira (cálculo de tempo de serviço, na categoria, no cargo)
- Recrutamento e Selecção (vagas → candidaturas → entrevistas)
- Formação e Desenvolvimento (cursos → sessões → inscrições → certificados)
- Benefícios Sociais (subsídios, assistência médica, pedidos)
- Ocorrências Disciplinares
- Folha de Pagamento (períodos, itens, títulos de vencimento)
- Arquivo (categorias hierárquicas, documentos com versões, partilhas, aprovação)
- Reforma e Aposentação (elegibilidade, processo, histórico pós-reforma)
- Portal do Funcionário (self-service)
- Dashboard e Relatórios (visão geral, aniversariantes, rotatividade, evolução salarial)



### 2.4 Infraestrutura
- Docker Compose (PHP 8.2 + Nginx + MySQL 8.0 + Redis + phpMyAdmin)
- Queue Horizon com Redis
- WebSockets Reverb (tempo real)
- Scramble para documentação automática da API
- Testes com PHPUnit (292 testes)

---

## 3. Possibilidades de Escalamento

### 3.1 Escalamento Horizontal (Mais Tráfego)

#### Web Servers (PHP/Nginx)
```
            Load Balancer (HAProxy / Nginx)
                    /              \
          Nginx (node 1)      Nginx (node 2)
               |                    |
           PHP-FPM (node 1)    PHP-FPM (node 2)
               |                    |
           +---------------------------+
           |    MySQL (Primary)        |
           |    Redis Cluster          |
           +---------------------------+
```

**O que já suporta:**
- Sessões stateless (Sanctum tokens) — qualquer node pode servir qualquer request
- Queue Horizon com Redis — jobs processados por qualquer worker
- Cache centralizada em Redis —所有 nodes partilham a mesma cache


#### Filas e Workers

A aplicação já usa Horizon com Redis. Isto permite escalar workers horizontalmente:

```bash
# Escalar workers no servidor actual
php artisan horizon:work --queue=high,default

# Ou em servidores separados (escalamento horizontal)
# Basta apontar para o mesmo Redis
```



#### WebSockets (Reverb)

Laravel Reverb suporta escalamento horizontal nativo com Redis pub/sub. Para escalar:
- Adicionar mais nós Reverb atrás de um load balancer TCP
- Configurar Redis como driver de broadcasting (já está)

### 3.2 Escalamento Funcional (Mais Módulos)

A arquitetura foi desenhada para adicionar módulos sem tocar no código existente:

```bash
# Criar módulo completo em 1 comando
php artisan make:module RH/NovoModulo --all
```

**O template gerado:**
```
app/
├── Models/NovoModulo.php
├── Repositories/NovoModuloRepository.php
├── Services/NovoModuloService.php
├── Http/Controllers/NovoModuloController.php
└── Http/Requests/NovoModuloRequest.php
```

**Exemplos de módulos futuros que encaixam sem refactor:**
- **Gestão de Frota** (veículos, motoristas, rotas, manutenção)
- **Gestão de Activos** (inventário, movimentações, amortizações)
- **Helpdesk / Chamados** (tickets, SLAs, atribuição)
- **Gestão Documental Avançada** (workflows, assinatura digital, OCR)
- **Ponto Biométrico** (integração com APIs externas de relógios de ponto)
- **Folha de Pagamento Avançada** (processamento automático, integração com banca)
- **Recrutamento** (portal de candidaturas público, testes online)
- **Avaliação 360º** (feedback anónimo, múltiplos avaliadores)
- **Objectivos Estratégicos** (OKRs, KPI dashboard)

Cada novo módulo segue o mesmo padrão e usa os mesmos `AbstractService` / `AbstractRepository` / `AbstractController` — sem duplicação de código.

### 3.3 Escalamento de Equipa

**Vários developers podem trabalhar em paralelo porque:**

| Característica | Benefício |
|---------------|-----------|
| Rotas separadas por ficheiro | Sem conflitos de merge em `api.php` |
| Controllers por módulo | Cada developer no seu controller |
| Services por módulo | Lógica de negócio isolada |
| Repositories por módulo | Queries independentes |
| Migrations independentes | Cada módulo com sua migration |

**Estimativa de produtividade:**
- Módulo CRUD simples (e.g., Tipos de Documento): **30 min** (migration + make:module + rotas)
- Módulo com lógica complexa (e.g., Recrutamento): **2-3 dias** (4 tabelas, validações, fluxos)
- Integração com API externa (e.g., envio de SMS): **1 dia** (Service + Notification + config)

### 3.4 Escalamento Geográfico (Multi-País / Multi-Empresa)

A estrutura actual permite adicionar um campo `company_id` ou `country_id` em todas as tabelas RH sem quebrar a API:

```php
// Exemplo: adicionar multi-empresa no AbstractRepository
public function index(array $filters = [])
{
    $query = $this->model->query();
    $query->where('company_id', auth()->user()->company_id);
    // ... resto dos filtros
}
```

**Abordagens possíveis:**
1. **Single DB + company_id** — mais simples, uma BD com todos os dados separados por empresa
2. **Multi-Tenant com DB separada** — cada empresa tem a sua BD, mais isolamento
3. **Microserviços** — cada módulo RH vira um serviço independente (ver secção 3.6)

### 3.5 Milestone: 10.000 Funcionários / 100.000 Transacções / Mês

**Cenário:** Uma instituição pública provincial com ~10.000 funcionários.

#### Gargalos Identificados e Soluções

| Gargalo | Solução Imediata (arquitectura actual) | Solução Avançada |
|---------|----------------------------------------|-------------------|
| **Dashboard com muitos cálculos** | Cache dos resultados (Redis, TTL 1h) | Materialized Views no MySQL |
| **Relatório mensal de ponto** | Paginação + filtros no AbstractRepository | Relatórios pré-calculados (tabela agregada) |
| **Importação biométrica (CSV)** | Já usa Job assíncrono | Processamento em stream (Chunked reads) |
| **Geração de payslips** | Já é batch (`POST /generate/{period_id}`) | Gerar em job de fundo com notificação |
| **Notificações em massa** | Já são database + mail | Fila dedicada + template engine |
| **Pesquisa de arquivos** | `GET /search` com índices | Elasticsearch / Meilisearch |

#### Cache Estratégica (Já implementável)

```php
// DashboardService com cache Redis
public function overview(): array
{
    return Cache::remember('dashboard:overview', 3600, function () {
        // cálculos pesados
    });
}
```

#### Índices MySQL (Já existentes)

Todas as foreign keys têm índices. Adicionar índices compostos para as queries mais frequentes:
- `(employee_id, data)` para attendance
- `(department_id, status)` para employees
- `(employee_id, ano)` para leave_plans

### 3.6 Evolução para Microserviços

Se um dia for necessário separar em serviços independentes, a arquitectura actual facilita porque:

| Módulo | Potencial Microserviço | API já isolada |
|--------|------------------------|----------------|
| Auth + Users | `auth-service` | `/api/v1/auth/*` |
| RH Core | `rh-service` | `/api/v1/rh/*` ||
| Arquivo | `archive-service` | `/api/v1/rh/archive/*` |
| Notificações | `notification-service` | via fila Horizon |

**Estratégia de migração (Strangler Fig):**
1. Extrair Services mais independentes (KYT, Archive)
2. Cada serviço ganha sua própria API e BD
3. API Gateway roteia pedidos
4. Comunicação entre serviços via Redis / RabbitMQ

A camada Service já contém toda a lógica de negócio isolada do HTTP — o core do negócio já está pronto para ser extraído.

---

## 4. Risco vs Benefício da Arquitectura Actual

| Aspecto | Risco | Mitigação |
|---------|-------|-----------|
| **Abstração genérica** | Performance ligeiramente inferior a queries manuais | Optimizações pontuais com indexes + cache |
| **Multi-camadas** | Mais ficheiros para navegar | Organização clara por módulo + DOCUMENTACAO.md |
| **SoftDeletes** | Tabelas crescem mais | Purge periódico de registos com >5 anos |
| **Transactions** | Lock wait timeout em concorrência alta | Retry automático já implementado no AbstractRepository |

---

## 5. Conclusão

**Porque usar esta estrutura:**

1. **Produtividade imediata** — criar um novo módulo CRUD leva minutos, não dias
2. **Qualidade de código** — separação de responsabilidades, validação centralizada, logging automático
3. **Testabilidade** — 292 testes já escritos, cada camada testa-se isoladamente
4. **Segurança** — XSS sanitization, SoftDeletes, autorização RBAC, 2FA
5. **Baixo acoplamento** — módulos independentes, mudanças localizadas

**Escalabilidade realista:**

```
Amanhã:    +5 módulos novos (sem refactor)
           +3 developers (sem conflitos)
           Cache Redis no dashboard

3 meses:   10.000 funcionários
           100.000 transacções/mês
           Workers Horizon horizontais

6 meses:   
           Multi-empresa com company_id
           Elasticsearch para arquivo

1 ano:     Microserviços completos
           Multi-geografia
           Processamento em tempo real
```

A arquitectura actual não é apenas adequada para o presente — foi desenhada para crescer sem refactors catastróficos.
