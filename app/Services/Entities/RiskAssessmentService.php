<?php

namespace App\Services\Entities;

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
        $relationships = [
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
        ];

        $orderByParams = $orderByParams ?? ['created_at' => 'desc'];
        return $this->repository->index($paginate, $filterParams, $orderByParams, $relationships);
    }

    public function show($id)
    {
        $relationships =  [
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
        ];
        return $this->repository->show($id, $relationships);
    }

    public function store(array $data)
    {
        $data['user_id'] = Auth::id() ?? $data['user_id'];

        $riskAssessment = $this->repository->store($data);

        if (isset($data['beneficial_owners'])) {
            $this->beneficialOwnerService->createBeneficialOwner($data, $riskAssessment->id);
        }

        if (!empty($data['pep']) && $data['pep'] === true) {
            $this->pepService->createEntityPep($data['entity_id']);
        }

        $riskProducts = $this->indicatorTypeRepository->getByIds($data['product_risk']);
        $this->productRiskService->storeProductRisks($riskProducts, $riskAssessment->id);

        $this->loadRelations($riskAssessment);
        $entityType = $riskAssessment['entity']['entity_type'];
        $formula = $this->riskFormulaRepository->findByEntityType($entityType);
        $totalRiskProduct = $riskProducts->sum('score');
        $total = $this->calculateTotalScore($riskAssessment, $totalRiskProduct, $formula);

        $diligence = $this->diligenceService->getDilligenceAssessment($total);

        $this->updateEntityRisk($riskAssessment, $total, $diligence);
        $riskAssessment->score = $total;
        $riskAssessment->color = $diligence->color;
        $riskAssessment->risk_level = $diligence->risk;
        $riskAssessment->diligence = $diligence->name;
        $riskAssessment->save();

        if ($riskAssessment->entity->entity_type == 1) {
            GenerateAlertsJob::dispatch($riskAssessment->entity->id, true, $riskAssessment->id)
                ->onQueue('high');
        } else {
            GenerateAlertsJob::dispatch($riskAssessment->entity->id, true, $riskAssessment->id)
                ->onQueue('high');
        }

        return $riskAssessment;
    }

    private function loadRelations($riskAssessment): void
    {
        $riskAssessment->load([
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

    private function calculateTotalScore($riskAssessment, $totalRiskProduct, $formula): int
    {


        $fromEstablishment = $riskAssessment->form_establishment == 0 ? 1 : 3;
        $statusResidence = $riskAssessment->status_residence == 0 ? 1 : 3;

        $santion = $riskAssessment->santion == 0 ? 0 : 20;
        $distributionChannel = $riskAssessment->distributionChannel == 0 ? 0 : 3;
        $pep = $riskAssessment->pep == 1 ? 20 : 0;

        return ($riskAssessment?->channel()?->first()?->score * $formula->channel) +
            ($riskAssessment?->indetificationCapacity()?->first()?->score * $formula->identification_capacity) +
            $riskAssessment?->profession()?->first()?->score * $formula->profession +
            ($fromEstablishment * $formula->fromEstablishment)  +
            ($statusResidence * $formula->status_residence) +
            ($santion * $formula->santion) +
            ($distributionChannel * $formula->distributionChannel) +
            ($pep * $formula->pep) +
            ($totalRiskProduct * $formula->product_risk);
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
}
