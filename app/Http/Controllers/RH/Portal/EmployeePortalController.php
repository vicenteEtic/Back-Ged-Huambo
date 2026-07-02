<?php

namespace App\Http\Controllers\RH\Portal;

use App\Http\Controllers\Controller;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Leave\LeavePlan;
use App\Models\RH\Payroll\Payslip;
use App\Services\RH\Career\CareerService;
use App\Services\RH\Leave\LeaveRequestService;
use App\Services\RH\Payroll\PayslipService;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class EmployeePortalController extends Controller
{
    public function __construct(
        protected CareerService $careerService,
        protected LeaveRequestService $leaveService,
        protected PayslipService $payslipService,
    ) {}

    public function profile()
    {
        try {
            $employee = $this->getEmployee();
            if (!$employee) {
                return response()->json(['error' => 'Funcionário não encontrado.'], Response::HTTP_NOT_FOUND);
            }
            return response()->json($employee->load(['department', 'position']));
        } catch (Exception $e) {
            Log::error('Portal profile error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function leaveBalance()
    {
        try {
            $employee = $this->getEmployee();
            if (!$employee) return response()->json(['error' => 'Funcionário não encontrado.'], Response::HTTP_NOT_FOUND);
            $year = request('year', now()->year);
            return response()->json($this->leaveService->balanceByEmployee($employee->id, $year));
        } catch (Exception $e) {
            Log::error('Portal leave balance error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function salaryHistory()
    {
        try {
            $employee = $this->getEmployee();
            if (!$employee) return response()->json(['error' => 'Funcionário não encontrado.'], Response::HTTP_NOT_FOUND);
            return response()->json($this->payslipService->historyByEmployee($employee->id));
        } catch (Exception $e) {
            Log::error('Portal salary history error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function career()
    {
        try {
            $employee = $this->getEmployee();
            if (!$employee) return response()->json(['error' => 'Funcionário não encontrado.'], Response::HTTP_NOT_FOUND);
            return response()->json($this->careerService->calculate($employee));
        } catch (Exception $e) {
            Log::error('Portal career error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function benefits()
    {
        try {
            $employee = $this->getEmployee();
            if (!$employee) return response()->json(['error' => 'Funcionário não encontrado.'], Response::HTTP_NOT_FOUND);
            $benefits = \App\Models\RH\Benefit\EmployeeBenefit::with('benefitType')
                ->where('employee_id', $employee->id)
                ->where('status', 'active')
                ->get();
            return response()->json($benefits);
        } catch (Exception $e) {
            Log::error('Portal benefits error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function payslipDownload(int $id)
    {
        try {
            $employee = $this->getEmployee();
            if (!$employee) return response()->json(['error' => 'Funcionário não encontrado.'], Response::HTTP_NOT_FOUND);

            $payslip = Payslip::where('id', $id)->where('employee_id', $employee->id)->firstOrFail();
            $payslip = $this->payslipService->markDownloaded($id);
            return response()->json($payslip->load('period'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Título não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Portal payslip download error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getEmployee(): ?Employee
    {
        return Employee::where('user_id', auth()->id())->first();
    }
}
