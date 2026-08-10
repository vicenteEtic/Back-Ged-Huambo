<?php

namespace App\Http\Controllers\RH\Declaration;

use App\Enum\DeclarationTypeEnum;
use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Declaration\DeclarationTypeForm;
use App\Services\RH\Declaration\DeclarationTypeService;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class DeclarationTypeController extends AbstractController
{
    protected ?string $logType = 'rh';

    protected ?string $nameEntity = 'Tipo de Declaração';

    protected ?string $fieldName = 'name';

    public function __construct(DeclarationTypeService $service)
    {
        $this->service = $service;
    }

    public function store(DeclarationTypeForm $request)
    {
        return $this->handleStore(
            fn () => $this->service->store($request->validated()),
            'Tipo de declaração criado por '.auth()->user()->first_name
        );
    }

    public function update(DeclarationTypeForm $request, $id)
    {
        return $this->handleUpdate(
            fn () => $this->service->update($request->validated(), $id),
            $id,
        );
    }

    /**
     * Campos (comuns + específicos) de um tipo de declaração — alimenta o formulário dinâmico.
     */
    public function fields(string $code)
    {
        try {
            $type = DeclarationTypeEnum::tryFrom($code);

            if (! $type) {
                return response()->json(['error' => 'Tipo de declaração não encontrado.'], Response::HTTP_NOT_FOUND);
            }

            $fields = [];

            foreach ($type->formFields() as $key) {
                $meta = config('declaracoes.fields.'.$key, [
                    'label' => ucfirst(str_replace('_', ' ', $key)),
                    'type' => 'text',
                    'group' => 'especifico',
                ]);

                if (! empty($meta['derived'])) {
                    continue;
                }

                $fields[$key] = $meta;
            }

            return response()->json([
                'type' => [
                    'code' => $type->value,
                    'name' => $type->label(),
                    'description' => $type->description(),
                ],
                'fields' => $fields,
            ]);
        } catch (Exception $e) {
            Log::error('Erro ao listar campos do tipo de declaração', ['message' => $e->getMessage()]);

            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
