<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ModelRelationsController extends Controller
{
    private const MODELS = [
        'department' => \App\Models\RH\Department\Department::class,
        'position' => \App\Models\RH\Position\Position::class,
        'employee' => \App\Models\RH\Employee\Employee::class,
        'area' => \App\Models\RH\Area\Area::class,
        'job_opening' => \App\Models\RH\Recruitment\JobOpening::class,
        'candidate' => \App\Models\RH\Recruitment\Candidate::class,
        'application' => \App\Models\RH\Recruitment\Application::class,
        'interview' => \App\Models\RH\Recruitment\Interview::class,
        'training_course' => \App\Models\RH\Training\TrainingCourse::class,
        'training_session' => \App\Models\RH\Training\TrainingSession::class,
        'training_enrollment' => \App\Models\RH\Training\TrainingEnrollment::class,
        'training_certificate' => \App\Models\RH\Training\TrainingCertificate::class,
        'performance_cycle' => \App\Models\RH\Performance\PerformanceCycle::class,
        'performance_goal' => \App\Models\RH\Performance\PerformanceGoal::class,
        'performance_evaluation' => \App\Models\RH\Performance\PerformanceEvaluation::class,
        'evaluation_criterion' => \App\Models\RH\Performance\EvaluationCriterion::class,
        'benefit_type' => \App\Models\RH\Benefit\BenefitType::class,
        'employee_benefit' => \App\Models\RH\Benefit\EmployeeBenefit::class,
        'benefit_claim' => \App\Models\RH\Benefit\BenefitClaim::class,
        'medical_assistance' => \App\Models\RH\Benefit\MedicalAssistance::class,
        'disciplinary_type' => \App\Models\RH\Disciplinary\DisciplinaryType::class,
        'disciplinary_record' => \App\Models\RH\Disciplinary\DisciplinaryRecord::class,
        'functional_history' => \App\Models\RH\FunctionalHistory\FunctionalHistory::class,
        'payroll_period' => \App\Models\RH\Payroll\PayrollPeriod::class,
        'payroll_item' => \App\Models\RH\Payroll\PayrollItem::class,
        'payslip' => \App\Models\RH\Payroll\Payslip::class,
        'attendance' => \App\Models\RH\Attendance\Attendance::class,
        'shift' => \App\Models\RH\Attendance\Shift::class,
        'shift_assignment' => \App\Models\RH\Attendance\ShiftAssignment::class,
        'leave_type' => \App\Models\RH\Leave\LeaveType::class,
        'leave_request' => \App\Models\RH\Leave\LeaveRequest::class,
        'leave_plan' => \App\Models\RH\Leave\LeavePlan::class,
        'employee_document' => \App\Models\RH\EmployeeDocument\EmployeeDocument::class,
        'archive_category' => \App\Models\RH\Archive\ArchiveCategory::class,
        'archive_document' => \App\Models\RH\Archive\ArchiveDocument::class,
        'progression_request' => \App\Models\RH\Career\ProgressionRequest::class,
        'progression_rule' => \App\Models\RH\Career\ProgressionRule::class,
        'retirement_process' => \App\Models\RH\Career\RetirementProcess::class,
    ];

    public function index(): JsonResponse
    {
        $result = [];
        foreach (self::MODELS as $alias => $class) {
            $result[$alias] = $this->getRelations(new $class);
        }
        return response()->json(['data' => $result]);
    }

    public function show(string $model): JsonResponse
    {
        $class = self::MODELS[$model] ?? null;

        if (!$class) {
            return response()->json([
                'error' => "Modelo '{$model}' não encontrado. Modelos disponíveis: " . implode(', ', array_keys(self::MODELS))
            ], 404);
        }

        $relations = $this->getRelations(new $class);

        return response()->json([
            'model' => $model,
            'relations' => $relations,
            'example' => "/api/rh/{$this->getModelPath($model)}?relationships[]=" . ($relations[0] ?? ''),
        ]);
    }

    private function getRelations(object $model): array
    {
        $relations = [];
        $reflection = new \ReflectionClass($model);

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() === get_class($model)
                && $method->getNumberOfParameters() === 0
                && !in_array($method->getName(), ['boot', 'initialize', 'booted', 'toArray', 'toArrayableRelations'])
                && !str_starts_with($method->getName(), '__')
            ) {
                $returnType = $method->getReturnType();
                if ($returnType instanceof \ReflectionNamedType
                    && is_a($returnType->getName(), \Illuminate\Database\Eloquent\Relations\Relation::class, true)
                ) {
                    $relations[] = $method->getName();
                }
            }
        }

        return $relations;
    }

    private function getModelPath(string $alias): string
    {
        $paths = [
            'department' => 'departments',
            'position' => 'positions',
            'employee' => 'employees',
            'job_opening' => 'recruitment/job-openings',
            'candidate' => 'recruitment/candidates',
            'application' => 'recruitment/applications',
            'interview' => 'recruitment/interviews',
            'training_course' => 'training/courses',
            'training_session' => 'training/sessions',
            'training_enrollment' => 'training/enrollments',
            'training_certificate' => 'training/certificates',
            'performance_cycle' => 'performance/cycles',
            'performance_goal' => 'performance/goals',
            'performance_evaluation' => 'performance/evaluations',
            'evaluation_criterion' => 'performance/criteria',
            'benefit_type' => 'benefits/types',
            'employee_benefit' => 'benefits/employee-benefits',
            'benefit_claim' => 'benefits/claims',
            'medical_assistance' => 'benefits/medical',
            'disciplinary_type' => 'disciplinary/types',
            'disciplinary_record' => 'disciplinary/records',
            'functional_history' => 'functional-history',
            'payroll_period' => 'payroll/periods',
            'payroll_item' => 'payroll/items',
            'payslip' => 'payslips',
            'attendance' => 'attendance',
            'shift' => 'attendance/shifts',
            'shift_assignment' => 'attendance/shift-assignments',
            'employee_document' => 'employees/documents',
            'archive_category' => 'archive/categories',
            'archive_document' => 'archive/documents',
            'progression_request' => 'progression/requests',
            'progression_rule' => 'career/rules',
            'retirement_process' => 'retirement',
        ];

        return $paths[$alias] ?? $alias;
    }
}
