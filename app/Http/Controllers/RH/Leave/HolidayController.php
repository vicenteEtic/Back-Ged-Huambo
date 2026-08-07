<?php

namespace App\Http\Controllers\RH\Leave;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Leave\HolidayRequest;
use App\Services\RH\Leave\HolidayService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HolidayController extends AbstractController
{
    protected ?string $logType = 'rh';

    protected ?string $nameEntity = 'Feriado';

    protected ?string $fieldName = 'name';

    public function __construct(HolidayService $service)
    {
        $this->service = $service;
    }

    public function store(HolidayRequest $request)
    {
        return $this->handleStore(
            fn () => $this->service->store($request->validated()),
        );
    }

    public function update(HolidayRequest $request, $id)
    {
        return $this->handleUpdate(
            fn () => $this->service->update($request->validated(), $id),
            $id,
        );
    }

    /**
     * Sincroniza feriados a partir de date.nager.at.
     */
    public function sync(Request $request)
    {
        try {
            $year = (int) $request->input('year', now()->year);
            $country = $request->input('country', 'AO');

            $count = $this->service->syncFromNager($year, $country);

            return response()->json([
                'message' => "{$count} feriados de {$year} sincronizados com sucesso.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
