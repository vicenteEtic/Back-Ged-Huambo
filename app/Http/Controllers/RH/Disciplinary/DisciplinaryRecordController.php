<?php

namespace App\Http\Controllers\RH\Disciplinary;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Disciplinary\DisciplinaryRecordRequest;
use App\Services\RH\Disciplinary\DisciplinaryRecordService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class DisciplinaryRecordController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'DisciplinaryRecord';
    protected ?string $fieldName = 'id';
    public function __construct(DisciplinaryRecordService $service) { $this->service = $service; }

    public function store(DisciplinaryRecordRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $model = $this->service->store($request->validated());
            $this->logToDatabase(type: 'rh', level: 'info', customMessage: 'Disciplinary record created by ' . Auth::user()->first_name);
            DB::commit();
            return response()->json($model, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack(); $this->logRequest($e);
            Log::error('Error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(DisciplinaryRecordRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $model = $this->service->update($request->validated(), $id);
            $this->logToDatabase(type: 'rh', level: 'info', customMessage: 'Disciplinary record updated by ' . Auth::user()->first_name);
            DB::commit();
            return response()->json($model, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack(); return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack(); $this->logRequest($e);
            Log::error('Error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
