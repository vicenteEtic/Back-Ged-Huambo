<?php

use App\Http\Controllers\Process\ProcessController;
use Illuminate\Support\Facades\Route;

// === CRUD Base ===
Route::get('/', [ProcessController::class, 'index'])->name('process.index')->middleware(['can:processos-show']);
Route::post('/', [ProcessController::class, 'store'])->name('process.store')->middleware(['can:processos-create']);
Route::get('{id}', [ProcessController::class, 'show'])->name('process.show')->middleware(['can:processos-show']);
Route::put('{id}', [ProcessController::class, 'update'])->name('process.update')->middleware(['can:processos-edit']);
Route::delete('{id}', [ProcessController::class, 'destroy'])->name('process.destroy')->middleware(['can:processos-delete']);

// === Listagens ===
Route::get('inbox/user', [ProcessController::class, 'inbox'])->name('process.inbox')->middleware(['can:processos-show']);
Route::get('outbox/user', [ProcessController::class, 'outbox'])->name('process.outbox')->middleware(['can:processos-show']);
Route::get('history/all', [ProcessController::class, 'history'])->name('process.history')->middleware(['can:processos-show']);

// === Workflow: Expediente → Chefe ===
Route::post('{id}/dispatch-to-chief', [ProcessController::class, 'dispatchToChief'])->name('process.dispatchToChief')->middleware(['can:processos-dispatch']);

// === Workflow: Chefe distribui a áreas ===
Route::post('{id}/dispatch-to-areas', [ProcessController::class, 'dispatchToAreas'])->name('process.dispatchToAreas')->middleware(['can:processos-dispatch']);

// === Gestão de técnicos por assignment ===
Route::post('{id}/assignments/{assignmentId}/add-technician', [ProcessController::class, 'addTechnician'])->name('process.addTechnician')->middleware(['can:processos-assign']);
Route::delete('{id}/assignments/{assignmentId}/technicians/{userId}', [ProcessController::class, 'removeTechnician'])->name('process.removeTechnician')->middleware(['can:processos-assign']);
Route::post('{id}/assignments/{assignmentId}/make-public', [ProcessController::class, 'makePublic'])->name('process.makePublic')->middleware(['can:processos-assign']);

// === Tratamento por técnico ===
Route::post('{id}/assignments/{assignmentId}/technicians/{technicianId}/start', [ProcessController::class, 'startByTechnician'])->name('process.startByTechnician')->middleware(['can:processos-edit']);
Route::post('{id}/assignments/{assignmentId}/technicians/{technicianId}/submit', [ProcessController::class, 'submitByTechnician'])->name('process.submitByTechnician')->middleware(['can:processos-edit']);

// === Validação de assignment ===
Route::post('{id}/assignments/{assignmentId}/validate', [ProcessController::class, 'validateAssignment'])->name('process.validateAssignment')->middleware(['can:processos-validate']);
Route::post('{id}/assignments/{assignmentId}/correction', [ProcessController::class, 'correctionAssignment'])->name('process.correctionAssignment')->middleware(['can:processos-edit']);

// === Validação encadeada ===
Route::post('{id}/validate-chief', [ProcessController::class, 'validateByChief'])->name('process.validateByChief')->middleware(['can:processos-validate']);
Route::post('{id}/validate-director', [ProcessController::class, 'validateByDirector'])->name('process.validateByDirector')->middleware(['can:processos-validate']);
Route::post('{id}/correction', [ProcessController::class, 'requestCorrection'])->name('process.requestCorrection')->middleware(['can:processos-edit']);
Route::post('{id}/reject', [ProcessController::class, 'reject'])->name('process.reject')->middleware(['can:processos-delete']);
Route::post('{id}/close', [ProcessController::class, 'close'])->name('process.close')->middleware(['can:processos-close']);

// === Dados auxiliares ===
Route::get('{id}/movements', [ProcessController::class, 'movements'])->name('process.movements')->middleware(['can:processos-show']);
Route::get('{id}/comments', [ProcessController::class, 'comments'])->name('process.comments')->middleware(['can:processos-show']);
Route::post('{id}/comments', [ProcessController::class, 'storeComment'])->name('process.storeComment')->middleware(['can:processos-edit']);
Route::get('{id}/assignments', [ProcessController::class, 'listAssignments'])->name('process.listAssignments')->middleware(['can:processos-show']);
