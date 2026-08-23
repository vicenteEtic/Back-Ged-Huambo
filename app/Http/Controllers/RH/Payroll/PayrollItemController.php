<?php

namespace App\Http\Controllers\RH\Payroll;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Payroll\PayrollItemRequest;
use App\Helpers\PayrollCalculator;
use App\Models\RH\Employee\Employee;
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

    private array $numericFields = [
        'base_salary', 'transport_allowance', 'meal_allowance',
        'overtime', 'other_earnings', 'other_deductions',
    ];

    private function normalizeNumericFields(array $input): array
    {
        foreach ($this->numericFields as $field) {
            $input[$field] = isset($input[$field]) && is_numeric($input[$field]) ? $input[$field] : 0;
        }

        return $input;
    }

    /**
     * Salário base: valor enviado tem prioridade; sem valor, usa o salário
     * base da categoria do funcionário (quadro de carreiras) e, em último
     * caso, o salário base registado no próprio funcionário.
     */
    private function resolveBaseSalary(array $item, ?int $fallbackEmployeeId = null): array
    {
        if (isset($item['base_salary']) && is_numeric($item['base_salary'])) {
            return $item;
        }

        $employeeId = $item['employee_id'] ?? $fallbackEmployeeId;

        if (!$employeeId) {
            return $item;
        }

        $employee = Employee::with('careerCategory')->find($employeeId);

        $item['base_salary'] = (float) ($employee?->careerCategory?->base_salary ?: $employee?->base_salary ?? 0);

        return $item;
    }

    public function store(PayrollItemRequest $request)
    {
        try {
            $this->logRequest();

            $validated = $request->validated();
            $periodId = $validated['payroll_period_id'];

            $items = DB::transaction(function () use ($validated, $periodId) {
                $created = [];

                foreach ($validated['items'] as $item) {
                    $item['payroll_period_id'] = $periodId;

                    $item = $this->resolveBaseSalary($item);

                    $input = collect($item)->except(self::$computedFields)->toArray();
                    $input = $this->normalizeNumericFields($input);
                    $created[] = $this->service->store(PayrollCalculator::calculate($input));
                }

                return $created;
            });

            $this->logToDatabase(
                type: 'rh', level: 'info',
                customMessage: count($items) . ' item(s) de folha de pagamento criado(s) por ' . auth()->user()->first_name
            );
            return response()->json($items, Response::HTTP_CREATED);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
            Log::error('Erro ao criar item(s) de folha de pagamento', [
                'message' => $e->getMessage(),
                'data' => $validated ?? $request->validated(),
            ]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function update(PayrollItemRequest $request, $id)
    {
        try {
            $this->logRequest();

            $existing = $this->service->show($id);

            $input = collect($request->validated())->except(self::$computedFields)->toArray();
            $input = $this->resolveBaseSalary($input, $existing->employee_id);
            $input = $this->normalizeNumericFields($input);
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
