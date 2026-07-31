# Documentação do Comando `make:module`

## Descrição
O comando `make:module` é um comando Artisan personalizado que automatiza a criação de múltiplos componentes para um módulo no Laravel. Ele pode criar Model, Repository, Service, Controller e FormRequest com uma única execução.

### Exemplo de Descrição
```bash
# Cria uma estrutura completa para o módulo de Usuários
php artisan make:module User --all
```

## Uso Básico
```bash
php artisan make:module NomeDoModulo
```

### Exemplo de Uso Básico
```bash
# Cria apenas a estrutura básica (sem componentes específicos)
php artisan make:module Product
```

## Opções
O comando aceita várias opções para especificar quais componentes devem ser criados:

| Opção    | Descrição                                      | Exemplo de Uso                          |
|----------|-----------------------------------------------|-----------------------------------------|
| `--m`    | Cria apenas o Model                           | `php artisan make:module User --m`      |
| `--r`    | Cria apenas o Repository                      | `php artisan make:module User --r`      |
| `--s`    | Cria apenas o Service                         | `php artisan make:module User --s`      |
| `--c`    | Cria apenas o Controller                      | `php artisan make:module User --c`      |
| `--f`    | Cria apenas o FormRequest                     | `php artisan make:module User --f`      |
| `--all`  | Cria todos os componentes                     | `php artisan make:module User --all`    |

## Exemplos Completos

1. **Criar todos os componentes de um módulo**:
```bash
php artisan make:module User --all
```
Saída esperada:
```
Model User criado em app/Models/User.php
Repository User criado em app/Repositories/UserRepository.php  
Service User criado em app/Services/UserService.php
Controller User criado em app/Http/Controllers/UserController.php
FormRequest User criado em app/Http/Requests/UserRequest.php
Arquivos criados com sucesso!
```

2. **Criar apenas Model e Controller**:
```bash
php artisan make:module Product --m --c
```
Saída esperada:
```
Model Product criado em app/Models/Product.php
Controller Product criado em app/Http/Controllers/ProductController.php
Arquivos criados com sucesso!
```

3. **Criar módulo em subdiretório**:
```bash
php artisan make:module Admin/User --all
```
Saída esperada:
```
Model User criado em app/Models/Admin/User.php  
Repository User criado em app/Repositories/Admin/UserRepository.php
Service User criado em app/Services/Admin/UserService.php
Controller User criado em app/Http/Controllers/Admin/UserController.php  
FormRequest User criado em app/Http/Requests/Admin/UserRequest.php
Arquivos criados com sucesso!
```

## Funcionalidades com Exemplos

### 1. Criação de Model
**Exemplo**: Para uma tabela `products` com colunas `name`, `price` e `description`:
```php
// app/Models/Product.php
class Product extends Model
{
    protected $table = 'products';
    protected $fillable = ['name', 'price', 'description'];
}
```

### 2. Criação de Repository  
**Exemplo**:
```php
// app/Repositories/ProductRepository.php
class ProductRepository extends AbstractRepository
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }
}
```

### 3. Criação de Service
**Exemplo**:
```php
// app/Services/ProductService.php  
class ProductService extends AbstractService
{
    public function __construct(ProductRepository $repository)
    {
        parent::__construct($repository);
    }
}
```

### 4. Criação de Controller
**Exemplo**:
```php
// app/Http/Controllers/ProductController.php
class ProductController extends AbstractController
{
    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }
}
```

### 5. Criação de FormRequest
**Exemplo**:
```php
// app/Http/Requests/ProductRequest.php
class ProductRequest extends BaseFormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'description' => 'nullable|string'
        ];
    }
}
```

## Estrutura de Diretórios com Exemplo
Para o comando:
```bash
php artisan make:module Admin/User --all
```

A estrutura gerada será:
```
app/
├── Models/
│   └── Admin/
│       └── User.php
├── Repositories/
│   └── Admin/
│       └── UserRepository.php
├── Services/
│   └── Admin/
│       └── UserService.php
└── Http/
    ├── Controllers/
    │   └── Admin/
    │       └── UserController.php
    └── Requests/
        └── Admin/
            └── UserRequest.php
```

## Observações com Exemplos
1. **Classes Abstratas**: O comando assume que existem:
```php
// app/Repositories/AbstractRepository.php
abstract class AbstractRepository {
    // implementação base
}

// app/Services/AbstractService.php  
abstract class AbstractService {
    // implementação base
}
```

2. **Tabela não existente**: Se executar para um model sem tabela:
```bash
php artisan make:module Temp --m
```
Gerará:
```php
// app/Models/Temp.php
class Temp extends Model
{
    protected $fillable = []; // Array vazio
}
```# API-UNIA
