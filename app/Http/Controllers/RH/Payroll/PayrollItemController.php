<?php

namespace App\Http\Controllers\RH\Payroll;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Payroll\PayrollItemRequest;
use App\Helpers\PayrollCalculator;
use App\Services\RH\Payroll\PayrollItemService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollItemController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Item de Folha de Pagamento';
    protected ?string $fieldName = 'id';

    public function __construct(PayrollItemService $service)
    {
        $this->service = $service;
    }

    private static array $computedFields = [
        'inss_deduction', 'irt_deduction', 'gross_pay', 'total_deductions', 'net_pay',
    ];

    public function store(PayrollItemRequest $request)
    {
        try {
            $this->logRequest();

            $input = collect($request->validated())->except(self::$computedFields)->toArray();
            $data = PayrollCalculator::calculate($input);

            $item = $this->service->store($data);
            $this->logToDatabase(
                type: 'rh', level: 'info',
                customMessage: 'Item de folha de pagamento criado por ' . auth()->user()->first_name
            );
            return response()->json($item, Response::HTTP_CREATED);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
            Log::error('Erro ao criar item de folha de pagamento', [
                'message' => $e->getMessage(),
                'data' => $data ?? $request->validated(),
            ]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function update(PayrollItemRequest $request, $id)
    {
        try {
            $this->logRequest();

            $input = collect($request->validated())->except(self::$computedFields)->toArray();
            $data = PayrollCalculator::calculate($input);

            $item = $this->service->update($data, $id);
            $this->logToDatabase(
                type: 'rh', level: 'info',
                customMessage: 'Item de folha de pagamento atualizado por ' . auth()->user()->first_name
            );
            return response()->json($item, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
            Log::error('Erro ao atualizar item de folha de pagamento', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
