<?php

namespace App\Http\Controllers\RH\Attendance;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Attendance\AbsenceJustificationRequest;
use App\Services\RH\Attendance\AbsenceJustificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AbsenceJustificationController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Justificação de Falta';
    protected ?string $fieldName = 'id';

    public function __construct(AbsenceJustificationService $service)
    {
        parent::__construct($service);
    }

    public function store(AbsenceJustificationRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(AbsenceJustificationRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }

    public function approve(Request $request, int $id)
    {
        try {
            $request->validate(['review_notes' => 'nullable|string']);
            $justification = $this->service->approve($id, $request->input('review_notes'));

            return response()->json($justification);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], 404);
        } catch (\Exception $e) {
            Log::error('Erro ao aprovar justificação de falta', ['message' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function reject(Request $request, int $id)
    {
        try {
            $request->validate(['review_notes' => 'nullable|string']);
            $justification = $this->service->reject($id, $request->input('review_notes'));

            return response()->json($justification);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], 404);
        } catch (\Exception $e) {
            Log::error('Erro ao rejeitar justificação de falta', ['message' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function downloadProof(int $id)
    {
        try {
            $justification = $this->service->show($id);

            if (empty($justification->proof_path)) {
                return response()->json(['error' => 'Esta justificação não possui comprovativo.'], 404);
            }

            if (! Storage::disk('public')->exists($justification->proof_path)) {
                return response()->json(['error' => 'Comprovativo não encontrado no servidor.'], 404);
            }

            return Storage::disk('public')->download($justification->proof_path);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], 404);
        } catch (\Exception $e) {
            Log::error('Erro ao abrir comprovativo de justificação', ['message' => $e->getMessage()]);

            return response()->json(['error' => 'Falha ao abrir o comprovativo.'], 400);
        }
    }
}
