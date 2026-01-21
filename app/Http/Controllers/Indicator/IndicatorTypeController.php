<?php

namespace App\Http\Controllers\Indicator;

use App\Enum\TypeIndicator;
use App\Http\Controllers\AbstractController;
use App\Http\Requests\Indicator\IndicatorTypeRequest;
use App\Services\Indicator\IndicatorTypeService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;

use function PHPUnit\Framework\returnArgument;

class IndicatorTypeController extends AbstractController
{
    protected ?string $logType = 'user';
    protected ?string $nameEntity = "Tipo de Indicador";
    protected ?string $fieldName = "indicator_id";

    public function __construct(IndicatorTypeService $service)
    {
        $this->service = $service;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(IndicatorTypeRequest $request, TypeIndicator $indicatorType)
    {
        try {
            $this->logRequest();
            $data = $request->validated();

            if ($indicatorType && in_array($indicatorType, TypeIndicator::cases(), true)) {
                $data['indicator_id'] = $indicatorType;
            }

            $indicatorTypeStore = $this->service->store($data);
            return response()->json($indicatorTypeStore, Response::HTTP_CREATED);
        } catch (Exception $e) {
            $this->logRequest($e);
            $this->logToDatabase(
                type: 'indicator',
                level: 'error',
                customMessage: "O usuário " . auth()->user()->first_name . " tentou criar um tipo de indicador, mas ocorreu um erro: "
            );
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */

    
 
    public function update(IndicatorTypeRequest $request, $id)
    {
        
        try {
            $this->logRequest();
            $indicatorTypeStore = $this->service->update($request->validated(), $id);
            $this->logToDatabase(
                type: 'indicator',
                level: 'info',
                customMessage: "O usuário " . auth()->user()->first_name . " atualizou o tipo de indicador {$indicatorTypeStore->name} com sucesso."
            );
            return response()->json($indicatorTypeStore, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            $this->logRequest($e);
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store specific indicator types.
     */
    public function storeCapacidadeIdentificacaoVerificacao(IndicatorTypeRequest $request)
    {
        $typeIndicator = $this->store($request, TypeIndicator::CAPACIDADE_IDENTIFICACAO_VERIFICACAO);
        $this->logToDatabase(
            type: 'user',
            level: 'info',
            customMessage: "O utilizador " . auth()->user()->first_name . " criou o tipo de indicador " . $request->name . " com sucesso."
        );
        return $typeIndicator;
    }


    public function storeTipoActividadePrincipal(IndicatorTypeRequest $request)
    {
        $typeIndicator = $this->store($request, TypeIndicator::TIPO_ACTIVIDADE_PRINCIPAL);
        $this->logToDatabase(
            type: 'user',
            level: 'info',
            customMessage: "O utilizador " . auth()->user()->first_name . " criou o tipo de indicador " . $request->name . " com sucesso."
        );
        return $typeIndicator;
    }

    public function storeTipoSeguro(IndicatorTypeRequest $request)
    {
        $typeIndicator = $this->store($request, TypeIndicator::TIPO_SEGURO);
        $this->logToDatabase(
            type: 'user',
            level: 'info',
            customMessage: "O utilizador " . auth()->user()->first_name . " criou o tipo de indicador " . $request->name . " com sucesso."
        );
        return $typeIndicator;
    }

    public function storeRiscoProdutosServicosTransacoes4(IndicatorTypeRequest $request)
    {
        $typeIndicator = $this->store($request, TypeIndicator::RISCO_PRODUTOS_SERVICOS_TRANSACOES_4);
        $this->logToDatabase(
            type: 'user',
            level: 'info',
            customMessage: "O utilizador " . auth()->user()->first_name . " criou o tipo de indicador " . $request->name . " com sucesso."
        );
        return $typeIndicator;
    }

    public function storeTipoActividadePrincipalColectiva(IndicatorTypeRequest $request)
    {
        $typeIndicator = $this->store($request, TypeIndicator::TIPO_ACTIVIDADE_PRINCIPAL_COLECTIVA);
        $this->logToDatabase(
            type: 'user',
            level: 'info',
            customMessage: "O utilizador " . auth()->user()->first_name . " criou o tipo de indicador " . $request->name . " com sucesso."
        );
        return $typeIndicator;
    }

    public function storeCanal(IndicatorTypeRequest $request)
    {
        $typeIndicator = $this->store($request, TypeIndicator::CANAIS);
        $this->logToDatabase(
            type: 'user',
            level: 'info',
            customMessage: "O utilizador " . auth()->user()->first_name . " criou o tipo de indicador " . $request->name . " com sucesso."
        );
        return $typeIndicator;
    }

    public function storeCae(IndicatorTypeRequest $request)
    {
        $typeIndicator = $this->store($request, TypeIndicator::CAE);
        $this->logToDatabase(
            type: 'user',
            level: 'info',
            customMessage: "O utilizador " . auth()->user()->first_name . " criou o tipo de indicador " . $request->name . " com sucesso."
        );
        return $typeIndicator;
    }



    public function getIndicatorsByFk()
    {
        $typeIndicator = $this->getIndicatorsByFk();
       
        return $typeIndicator;
    }

    
}
