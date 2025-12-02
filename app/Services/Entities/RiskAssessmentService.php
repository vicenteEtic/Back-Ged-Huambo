<?php

namespace App\Services\Entities;

use App\Enum\FormEstablishment;
use App\Enum\StatusResidence;
use App\Http\Resources\RiskAssessmentResource;
use InvalidArgumentException;
use App\Jobs\GenerateAlertsJob;
use App\Services\AbstractService;
use App\Services\Alert\AlertService;
use Illuminate\Support\Facades\Auth;
use App\Services\Diligence\DiligenceService;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Entities\RiskAssessmentRepository;
use App\Repositories\Entities\RiskFormulaRepository;
use App\Repositories\Indicator\IndicatorTypeRepository;
use Illuminate\Support\Facades\Log;

class RiskAssessmentService extends AbstractService
{
    public $riskFormulaRepository;
    private const MONTHS = [
        1 => 'Janeiro',
        2 => 'Fevereiro',
        3 => 'Março',
        4 => 'Abril',
        5 => 'Maio',
        6 => 'Junho',
        7 => 'Julho',
        8 => 'Agosto',
        9 => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro',
    ];
    private $relationships = [
        'entity',
        'user',
        'profession',
        'indetificationCapacity',
        'channel',
        'countryResidence',
        'category',
        'nationlity',
        'beneficialOwners',
        'productRisk',
        'productRisk.product',
        "riskFormula"
    ];
    public function __construct(
        RiskAssessmentRepository $repository,
        private readonly IndicatorTypeRepository $indicatorTypeRepository,
        private readonly DiligenceService $diligenceService,
        private readonly ProductRiskService $productRiskService,
        private readonly BeneficialOwnerService $beneficialOwnerService,
        private readonly PepService $pepService,
        RiskFormulaRepository $riskFormulaRepository,
        private AlertService $alertService
    ) {
        parent::__construct($repository);
        $this->riskFormulaRepository = $riskFormulaRepository;
    }

    public function index(?int $paginate, ?array $filterParams, ?array $orderByParams, $relationships = [])
    {
        $relationships = $this->relationships;
        $orderByParams = $orderByParams ?? ['created_at' => 'desc'];
   
         $assessment= $this->repository->index($paginate, $filterParams, $orderByParams, $relationships);
         return RiskAssessmentResource::collection($assessment);
        }

    public function show($id)
    {
        $relationships = $this->relationships;
         $assessment=$this->repository->show($id, $relationships);
         return new RiskAssessmentResource($assessment);
        
    }

    public function store(array $data)
    {
        $data['user_id'] = Auth::id() ?? $data['user_id'];


        $data['beneficialOwner'] = $this->countPepTrueBeneficialOwner($data);

        $riskAssessment = $this->repository->store($data);

        if (isset($data['beneficial_owners'])) {
            $this->beneficialOwnerService->createBeneficialOwner($data, $riskAssessment->id);
        }

        if (!empty($data['pep']) && $data['pep'] === true) {
            $this->pepService->createEntityPep($data['entity_id']);
        }

        $riskProducts = $this->indicatorTypeRepository->getByIds($data['product_risk']);
        $this->productRiskService->storeProductRisks($riskProducts, $riskAssessment->id);
        $entityType = $riskAssessment['entity']['entity_type'];
        $formula = $this->riskFormulaRepository->findByEntityType($entityType);
        // Atualizar o campo id_risk_formula
        $riskAssessment->update([
            'id_risk_formula' =>   $formula->id
        ]);
        $this->loadRelations($riskAssessment);

        $totalRiskProduct = $riskProducts->sum('score');
        $total = $this->calculateTotalScore($riskAssessment, $totalRiskProduct, $formula, $data['beneficialOwner']);

        $diligence = $this->diligenceService->getDilligenceAssessment($total);
        $this->updateEntityRisk($riskAssessment, $total, $diligence);
        $riskAssessment->score = $total;
        $riskAssessment->color = $diligence->color;
        $riskAssessment->risk_level = $diligence->risk;
        $riskAssessment->diligence = $diligence->name;
        $riskAssessment->save();

        GenerateAlertsJob::dispatch($riskAssessment->entity->id,  $riskAssessment)
            ->onQueue('high');


        return $riskAssessment;
    }

    private function loadRelations($riskAssessment): void
    {
        $riskAssessment->load([
            "riskFormula",
            'entity',
            'user',
            'profession',
            'indetificationCapacity',
            'channel',
            'countryResidence',
            'category',
            'nationlity',
            'beneficialOwners',
            'productRisk',
            'productRisk.product'

        ]);
    }
    private function calculateTotalScore($riskAssessment, $totalRiskProduct, $formula, $beneficialOwnerScore): float
    {
        // --- Scores básicos ---
        $baseScores = [
            'identification'    => $riskAssessment?->indetificationCapacity()?->first()?->score ?? 0,
            'profession'        => $riskAssessment?->profession()?->first()?->score ?? 0, // Particulares
            'activityCode'      => $riskAssessment?->category()?->first()?->score ?? 0,   // Empresas
            'nationality'       => $riskAssessment?->nationlity()?->first()?->score ?? 0,
            'countryResidence'  => $riskAssessment?->countryResidence()?->first()?->score ?? 0,
            'statusResidence'   => $riskAssessment->status_residence === StatusResidence::RESIDENTE ? 1 : 3,
            'formEstablishment' => $riskAssessment->form_establishment === FormEstablishment::PRESENCIAL ? 1 : 3,
            'processesReported' => $riskAssessment->processesReportedAuthoritie ? 3 : 0,
            'santion'           => $riskAssessment->santion ? 20 : 0,
            'pep'               => $riskAssessment->pep ? 3 : 0,
            'channel'           => $riskAssessment?->channel()?->first()?->score ?? 0,
            'totalRiskProduct'  => (float) $totalRiskProduct,
            'category'    => $riskAssessment?->category()?->first()?->score ?? 0,
        ];

        // --- Somar total dinamicamente ---
        $total = 0;

        if ($formula->entity_type == 2) { // Entidade Particular
            $total += $baseScores['identification'] * (float)$formula->identification_capacity;
            $total += $baseScores['profession'] * (float)$formula->profession;
            $total += $baseScores['nationality'] * (float)$formula->nationality;
            $total += $baseScores['countryResidence'] * (float)$formula->country_residence;
            $total += $baseScores['statusResidence'] * (float)$formula->status_residence;
            // $total += $baseScores['formEstablishment'] * (float)$formula->form_establishment;
            $total += $baseScores['processesReported'] * (float)$formula->processesReportedAuthoritie;
            $total += $baseScores['totalRiskProduct'] * (float)$formula->product_risk;
            $total += $baseScores['santion'] * (float)$formula->santion;;
            $total += $baseScores['pep'] * (float)$formula->pep;
            // $total += $beneficialOwnerScore;
            $total += $baseScores['channel'] * (float)$formula->channel;
        } else { // Entidade Coletiva
            $total += $baseScores['identification'] * (float)$formula->identification_capacity;
            $total += $baseScores['activityCode'] * (float)$formula->category;
            // $total += $baseScores['formEstablishment'] * (float)$formula->form_establishment;
            $total += $baseScores['countryResidence'] * (float)$formula->country_residence;
            $total += $baseScores['statusResidence'] * (float)$formula->status_residence;
            $total += $beneficialOwnerScore * (float)$formula->beneficialOwner;
            $total += $baseScores['totalRiskProduct'] * (float)$formula->product_risk;
            $total += $baseScores['processesReported'] * (float)$formula->processesReportedAuthoritie;
            $total += $baseScores['santion'] * (float)$formula->santion;
            $total += $baseScores['channel'] * (float)$formula->channel;
            $total += $baseScores['category'] * (float)$formula->category;
        }

        return $total;
    }




    private function updateEntityRisk($riskAssessment, $total, $diligence): void
    {
        $entity = $riskAssessment?->entity();
        $entity->update([
            'risk_level' => $diligence?->risk,
            'diligence' => $diligence?->name,
            'color' => $diligence?->color,
            'last_evaluation' => now()
        ]);
    }


    public function getTotalRiskLevelByCategory(): array
    {
        return $this->repository->totalRiskLevelByCategory();
    }

    public function getTotalRiskLevelByProfession(): array
    {
        return $this->repository->totalRiskLevelByProfession();
    }

    public function getTotalRiskLevelByChannel(): array
    {
        return $this->repository->totalRiskLevelByChannel();
    }

    public function getTotalRiskLevelByPep(): array
    {
        return $this->repository->totalRiskLevelByPep();
    }

    public function getTotalRiskLevelByCountryResidence(): array
    {
        return $this->repository->totalRiskLevelByCountryResidence();
    }
    public function getTotalRiskLevelByNationality(): array
    {
        return $this->repository->totalRiskLevelByNationality();
    }

    public function getTotalRiskLevelByProductRisk(): array
    {
        return $this->repository->totalRiskLevelByProductRisk();
    }

    public function getHeatMap(?int $year = null): array
    {
        $year = $this->validateYear($year ?? (int) date('Y'));
        $monthlyData = $this->repository->getMonthlyData($year);
        $years = $this->repository->getDistinctYears();

        return $this->formatResults($year, $monthlyData, $years);
    }


    private function formatResults(int $year, array $monthlyData, array $years): array
    {
        $formattedData = $this->processMonthlyData($monthlyData);

        return [
            'year' => $year,
            'diligences' => array_values($formattedData),
            'years' => $years,
        ];
    }


    private function processMonthlyData(array $monthlyData): array
    {
        $formattedData = [];

        foreach ($monthlyData as $data) {
            if (!isset($data['name'], $data['month'], $data['total'], $data['color'])) {
                continue; // Skip invalid data entries
            }

            $diligence = $data['name'];
            $month = $this->translateMonth((int) $data['month']);
            $total = (int) $data['total'];

            if ($total <= 0) {
                continue; // Skip zero or negative totals
            }

            if (!isset($formattedData[$diligence])) {
                $formattedData[$diligence] = [
                    'diligence' => $diligence,
                    'color' => $data['color'],
                    'data' => [],
                ];
            }

            $formattedData[$diligence]['data'][] = [
                'month' => $month,
                'total' => $total,
            ];
        }

        return $formattedData;
    }

    private function translateMonth(int $month): string
    {
        return self::MONTHS[$month] ?? 'Mês inválido';
    }

    private function validateYear(int $year): int
    {
        if ($year < 1900 || $year > (int) date('Y') + 1) {
            throw new InvalidArgumentException("Invalid year: {$year}");
        }

        return $year;
    }

    public function getTotalRiskAssessments(): int
    {
        return $this->repository->getTotalRiskAssessments();
    }

    public function getLastAssessment(int $limit = 3): ?Collection
    {
        return $this->repository->getLastAssessment($limit);
    }


    public function countPepTrueBeneficialOwner(array $data)
    {
        // Contar quantos beneficiários têm PEP = true
        $pepCount = 0;

        foreach ($data['beneficial_owners'] ?? [] as $owner) {
            if (!empty($owner->pep)) {
                $pepCount++;
            }
        }

        // Multiplicar o total por 3 (pontuação)
        return $pepCount * 3;
    }
}
