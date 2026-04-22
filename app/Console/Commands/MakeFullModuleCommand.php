<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeFullModuleCommand extends Command
{
    protected $signature = 'make:module {name} {--m} {--r} {--s} {--c}  {--f} {--all}';
    protected $description = 'Cria model, repositories, form request, controller e service para um módulo automaticamente usando o nome como caminho.';

    public function handle()
    {
        $name = $this->argument('name');

        // Extrai path e filename da função
        [$path, $filename, $namespacePath] = $this->getPathAndFilename($name);

        // Cria os arquivos de acordo com as flags passadas
        if ($this->option('m') or $this->option('all')) {
            $this->createModel($path, $filename, $namespacePath);
        }

        if ($this->option('r') or $this->option('all')) {
            $this->createRepository($path, $filename, $namespacePath);
        }

        if ($this->option('s') or $this->option('all')) {
            $this->createService($path, $filename, $namespacePath);
        }

        if ($this->option('c') or $this->option('all')) {
            $this->createController($path, $filename, $namespacePath);
        }
        if ($this->option('f') or $this->option('all')) {
            $this->createFormRequest($path, $filename, $namespacePath);
        }

        $this->info('Arquivos criados com sucesso!');
    }

    protected function getPathAndFilename($name)
    {
        // Detecta se há separadores (\ ou /) no nome fornecido
        $segments = preg_split('/[\/\\\\]+/', $name);

        // O último elemento é o nome do arquivo (classe)
        $filename = array_pop($segments);

        // O restante é o caminho do namespace (usa '\' como separador para o namespace)
        $namespacePath = implode('\\', $segments);

        // Para o caminho do diretório, usa o separador de diretórios adequado ao sistema de arquivos
        $path = implode(DIRECTORY_SEPARATOR, $segments);

        return [$path, $filename, $namespacePath];
    }

    protected function createModel($path, $filename, $namespacePath)
    {
        $tableName = Str::snake($filename);
        $fillable = [];

        // Verifica se a tabela existe no banco de dados
        if (\Schema::hasTable($tableName)) {
            // Pega as colunas da tabela
            $columns = \Schema::getColumnListing($tableName);

            // Remove colunas padrão que não devem ser fillable
            $fillable = array_diff(
                $columns,
                [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at'
                ]
            );
        }

        $fillableString = implode("', '", $fillable);

        $modelTemplate = "<?php

namespace App\Models\\{$namespacePath};

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class $filename extends Model
{
    use HasFactory;
    protected \$table = '$tableName';
    protected \$primaryKey = 'id';
    protected \$fillable = ['$fillableString'];
}";

        $directory = app_path("/Models/{$path}");
        File::ensureDirectoryExists($directory);
        File::put("$directory/{$filename}.php", $modelTemplate);

        $this->info("Model $filename criado em $directory");
    }

    protected function createRepository($path, $filename, $namespacePath)
    {
        $repositoryTemplate = "<?php
namespace App\Repositories\\{$namespacePath};

use App\Models\\{$namespacePath}\\{$filename};
use App\Repositories\AbstractRepository;

class {$filename}Repository extends AbstractRepository
{
    public function __construct({$filename} \$model)
    {
        parent::__construct(\$model);
    }
}";

        $directory = app_path("/Repositories/{$path}");
        File::ensureDirectoryExists($directory);
        File::put("$directory/{$filename}Repository.php", $repositoryTemplate);

        $this->info("Repository $filename criado em $directory");
    }

    protected function createService($path, $filename, $namespacePath)
    {
        $serviceTemplate = "<?php
namespace App\Services\\{$namespacePath};

use App\Repositories\\{$namespacePath}\\{$filename}Repository;
use App\Services\AbstractService;

class {$filename}Service extends AbstractService
{
    public function __construct({$filename}Repository \$repository)
    {
        parent::__construct(\$repository);
    }
}";

        $directory = app_path("/Services/{$path}");
        File::ensureDirectoryExists($directory);
        File::put("$directory/{$filename}Service.php", $serviceTemplate);

        $this->info("Service $filename criado em $directory");
    }

    protected function createController($path, $filename, $namespacePath)
    {
        // Converter o filename para o formato desejado na variável entity
        $entityName = lcfirst($filename); // Converte TesteFileName para testeFileName

        $controllerTemplate = "<?php
    
    namespace App\Http\Controllers\\{$namespacePath};
    
    use App\Http\Controllers\AbstractController;
    use App\Services\\{$namespacePath}\\{$filename}Service;
    use App\Http\Requests\\{$namespacePath}\\{$filename}Request;
    use Exception; 
use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\ModelNotFoundException;
    use Illuminate\Http\Response;
    
    class {$filename}Controller extends AbstractController
    {
        public function __construct({$filename}Service \$service)
        {
            \$this->service = \$service;
        }
    
        /**
         * Store a newly created resource in storage.
         */
        public function store({$filename}Request \$request)
        {
            try {
                \$this->logRequest();
                \${$entityName} = \$this->service->store(\$request->validated());
                return response()->json(\${$entityName}, Response::HTTP_CREATED);
            } catch (Exception \$e) {
                \$this->logRequest(\$e);
                return response()->json(\$e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    
        /**
         * Update the specified resource in storage.
         */
        public function update({$filename}Request \$request, \$id)
        {
            try {
                \$this->logRequest();
                \${$entityName} = \$this->service->update(\$request->validated(), \$id);
                return response()->json(\${$entityName}, Response::HTTP_OK);
            } catch (ModelNotFoundException \$e) {
                \$this->logRequest(\$e);
                return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
            } catch (Exception \$e) {
                \$this->logRequest(\$e);
                return response()->json(\$e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }";

        $directory = app_path("/Http/Controllers/{$path}");
        File::ensureDirectoryExists($directory);
        File::put("$directory/{$filename}Controller.php", $controllerTemplate);

        $this->info("Controller $filename criado em $directory");
    }

    protected function createFormRequest($path, $filename, $namespacePath)
    {
        $tableName = Str::snake($filename);
        $rules = [];

        // Verifica se a tabela existe no banco de dados
        if (\Schema::hasTable($tableName)) {
            // Pega as colunas da tabela
            $columns = \Schema::getColumnListing($tableName);

            // Remove colunas padrão que não devem estar nas rules
            $fillable = array_diff(
                $columns,
                [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at'
                ]
            );

            // Cria regras básicas para cada coluna (pode ser personalizado conforme necessidade)
            foreach ($fillable as $column) {
                $rules[] = "'$column' => 'required'";
            }
        }

        $rulesString = implode(",\n            ", $rules);

        // Template do FormRequest
        $formRequestTemplate = "<?php

namespace App\Http\Requests\\{$namespacePath};

use App\Http\Requests\BaseFormRequest;

class {$filename}Request extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            $rulesString
        ];
    }
}";

        // Verifica e cria o diretório caso não exista
        $directory = app_path("/Http/Requests/{$path}");
        File::ensureDirectoryExists($directory);

        // Cria o arquivo do FormRequest no diretório especificado
        File::put("$directory/{$filename}Request.php", $formRequestTemplate);

        // Exibe uma mensagem informando que o FormRequest foi criado
        $this->info("FormRequest {$filename} criado em $directory");
    }
}
