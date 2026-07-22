<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\AbstractService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;


/**
 * Ao usar o AbstractController é necessário criar manualmente os métodos store e update.
 */

abstract class AbstractController extends Controller
{
    protected mixed $service;
    protected ?string $nameEntity = "Entidade";
    protected ?string $fieldName = "name";
    protected ?string $logType = 'entity';
    protected bool $logRequest = true;

    public function __construct(AbstractService $service)
    {
        $this->service = $service;
    }

    protected function handleStore(callable $callback, ?string $createdMessage = null): \Illuminate\Http\JsonResponse
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $result = $callback();
            if ($createdMessage) {
                $this->logToDatabase(type: $this->logType, level: 'info', customMessage: $createdMessage);
            }
            DB::commit();
            return response()->json($result, Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error("Erro ao criar {$this->nameEntity}", ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    protected function handleUpdate(callable $callback, int|string $id, ?string $updatedMessage = null): \Illuminate\Http\JsonResponse
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $result = $callback();
            if ($updatedMessage) {
                $this->logToDatabase(type: $this->logType, level: 'info', customMessage: $updatedMessage);
            }
            DB::commit();
            return response()->json($result, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error("Erro ao atualizar {$this->nameEntity}", ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            if ($this->logRequest) {
                $this->logRequest();
                $this->logToDatabase(
                    type: $this->logType,
                    level: 'info',
                    customMessage: "O utilizador " . Auth::user()->first_name . " visualizou todos os registros no módulo {$this->nameEntity}",
                );
            }

            $filters = $request['filters'] ?? $request['filtersV2'];
            $service = $this->service->index($request['paginate'], $filters, $request['orderBy'], $request['relationships']);
            return response()->json($service);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            if ($this->logRequest) {
                $this->logRequest($e);
            }
            Log::error('Erro interno', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int|string $id)
    {
        try {
            if ($this->logRequest) {
                $this->logRequest();
            }

            $service = $this->service->show($id);

            if ($this->logRequest) {
                $this->logToDatabase(
                    type: $this->logType,
                    level: 'info',
                    customMessage: "O usuário " . auth()->user()->first_name . " visualizou o registro com a descrição: {$this->resolvePath($service,$this->fieldName)} no módulo {$this->nameEntity}",
                );
            }

            return response()->json($service);
        } catch (ModelNotFoundException $e) {
            if ($this->logRequest) {
                $this->logRequest($e);
                $this->logToDatabase(
                    type: $this->logType,
                    level: 'error',
                    customMessage: "Erro ao visualizar o registro {$id} em {$this->nameEntity}."
                );
            }
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            if ($this->logRequest) {
                $this->logRequest($e);
            }
            Log::error('Erro interno', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        try {
            if ($this->logRequest) {
                $this->logRequest();
            }

            $this->service->destroy($id);

            if ($this->logRequest) {
                $this->logToDatabase(
                    type: $this->logType,
                    level: 'info',
                    customMessage: "Registro {$id} removido com sucesso em {$this->nameEntity}."
                );
            }

            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $e) {
            if ($this->logRequest) {
                $this->logRequest($e);
                $this->logToDatabase(
                    type: $this->logType,
                    level: 'error',
                    customMessage: "Erro ao remover o registro {$id} em {$this->nameEntity}."
                );
            }
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            if ($this->logRequest) {
                $this->logRequest($e);
            }
            Log::error('Erro interno', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore(int $id)
    {
        try {
            $service = $this->service->restore($id);
            return response()->json($service, Response::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Erro interno', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
