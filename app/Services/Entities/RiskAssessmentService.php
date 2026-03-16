<?php

namespace App\Services\Entities;

use App\Enum\FormEstablishment;
use App\Enum\StatusResidence;
use App\Http\Resources\RiskAssessmentCollection;
use App\Http\Resources\RiskAssessmentResource;
use App\Http\Resources\RiskAssessmentResourceCollection;
use App\Http\Resources\RiskAssessmentResourceGET;
use App\Services\Indicator\IndicatorTypeService;
use InvalidArgumentException;
use App\Jobs\GenerateAlertsJob;
use App\Jobs\SendGrupoAlertEmailJob;
use App\Models\Entities\RiskAssessment;
use App\Repositories\Alert\AlertRepository;
use App\Services\AbstractService;
use App\Services\Alert\AlertService;
use Illuminate\Support\Facades\Auth;
use App\Services\Diligence\DiligenceService;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Entities\RiskAssessmentRepository;
use App\Repositories\Entities\RiskFormulaRepository;
use App\Repositories\Indicator\IndicatorTypeRepository;
use Carbon\Carbon;
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
        "riskFormula",
        'beneficial'
    ];

    public function __construct(
        RiskAssessmentRepository $repository,
        private readonly IndicatorTypeRepository $indicatorTypeRepository,
        private readonly DiligenceService $diligenceService,
        private readonly ProductRiskService $productRiskService,
        private readonly BeneficialOwnerService $beneficialOwnerService,
        private readonly PepService $pepService,
        private readonly BeneficialService $beneficialService,
        RiskFormulaRepository $riskFormulaRepository,

        private AlertRepository $alertRepository // ← agora injetado corretamente
    ) {
        parent::__construct($repository);
        $this->riskFormulaRepository = $riskFormulaRepository;
        $this->alertRepository = $alertRepository; // ← garantir atribuição

    }

    public function index(?int $paginate, ?array $filterParams, ?array $orderByParams, $relationships = [])
    {
        $relationships = $this->relationships;
        $orderByParams = $orderByParams ?? ['created_at' => 'desc'];

        $assessment = $this->repository->index($paginate, $filterParams, $orderByParams, $relationships);
        return $assessment;
    }

    public function show($id)
    {
        $relationships = $this->relationships;
        $assessment = $this->repository->show($id, $relationships);
        return new RiskAssessmentResource($assessment);
    }


    public function findModelWithProducts(int $id): RiskAssessment
    {
        return RiskAssessment::with([
            'productRisk',
            'productRisk.product',
        ])->findOrFail($id);
    }

    public function store(array $data)
    {
        Log::info('RISK STORE: Iniciando store', ['data' => $data]);

        try {
            $requiredFields = [
                $data['professionP'] ?? null,
                $data['channel'] ?? null,
                $data['country_residence'] ?? null,
                $data['nationality'] ?? null,
                $data['identification_capacity'] ?? null,
            ];



            $data['user_id'] = Auth::id() ?? $data['user_id'];
            Log::info('RISK STORE: User ID definido', ['user_id' => $data['user_id']]);

            $data['beneficialOwner'] = $this->countRiskPoints($data);
            Log::info('RISK STORE: BeneficialOwner calculado', ['beneficialOwner' => $data['beneficialOwner']]);

            $riskAssessment = $this->repository->store($data);
            Log::info('RISK STORE: RiskAssessment criado', ['riskAssessment_id' => $riskAssessment->id ?? null]);

            $this->handleBeneficialOwners($data, $riskAssessment);
            Log::info('RISK STORE: BeneficialOwners processados');

            $this->handlePEP($data);
            Log::info('RISK STORE: PEP processado');

            $riskProducts = $this->handleProductRisks($data, $riskAssessment);
            Log::info('RISK STORE: ProductRisks processados', ['count' => $riskProducts->count()]);

            $formula = $this->riskFormulaRepository->findByEntityType($riskAssessment['entity']['entity_type']);
            Log::info('RISK STORE: Formula encontrada', ['formula_id' => $formula->id]);

            $riskAssessment->update(['id_risk_formula' => $formula->id]);
            $this->loadRelations($riskAssessment);
            Log::info('RISK STORE: Relações carregadas');


            // Se todos os campos não forem null, processa
            if (!collect($requiredFields)->contains(fn($f) => is_null($f))) {
                $totalRiskProduct = $riskProducts->sum('score');
                $total = $this->calculateTotalScore($riskAssessment, $totalRiskProduct, $formula, $data['beneficialOwner']);
                Log::info('RISK STORE: Total de risco calculado', ['total' => $total]);

                $diligence = $this->diligenceService->getDilligenceAssessment($total);
                Log::info('RISK STORE: Diligence definida', ['diligence' => $diligence]);

                $nextReassessmentDate = $this->getNextReassessmentDate($diligence);
                $this->updateEntityRisk($riskAssessment, $total, $diligence, $nextReassessmentDate);
                Log::info('RISK STORE: EntityRisk atualizado');

                $riskAssessment->score = $total;
                $riskAssessment->color = $diligence->color;
                $riskAssessment->risk_level = $diligence->risk;
                $riskAssessment->diligence = $diligence->name;
                $riskAssessment->reassessmentPeriod = $nextReassessmentDate;

                $riskAssessment->save();
                Log::info('RISK STORE: RiskAssessment salvo', ['riskAssessment_id' => $riskAssessment->id]);

                $this->handleAlerts($riskAssessment, $diligence);
                Log::info('RISK STORE: Alerts processados');
                // Chama job de geração de alertas
                try {
                    $this->dispatchGenerateAlertsJob($data, $riskAssessment);
                    Log::info('RISK STORE: GenerateAlertsJob disparado');
                } catch (\Throwable $th) {
                    //throw $th;
                }
            }


            return $riskAssessment;
        } catch (\Throwable $e) {
            Log::error('RISK STORE: Erro ao processar registro', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    private function handleBeneficialOwners(array $data, $riskAssessment): void
    {
        if (isset($data['beneficial_owners'])) {
            $this->beneficialOwnerService->createBeneficialOwner($data, $riskAssessment->id);
        }
        if (isset($data['beneficial'])) {
            $this->beneficialService->createBeneficial($data, $riskAssessment->id);
        }
    }

    private function handlePEP(array $data): void
    {
        if (!empty($data['pep']) && ($data['pep'] === true || $data['pep'] === 1)) {
            $this->pepService->createEntityPep($data['entity_id']);
        }
    }

    private function handleProductRisks(array $data, $riskAssessment)
    {
        $riskProducts = $this->indicatorTypeRepository->getByIds($data['product_risk']);
        $this->productRiskService->storeProductRisks($riskProducts, $riskAssessment->id);
        return $riskProducts;
    }

    private function getNextReassessmentDate($diligence): ?string
    {
        if ($diligence->name === "Cliente Inaceitável") return null;

        $years = (int) filter_var($diligence?->reassessmentPeriod, FILTER_SANITIZE_NUMBER_INT);
        return Carbon::now()->addYears($years)->format('Y-m-d');
    }

    private function handleAlerts($riskAssessment, $diligence): void
    {
        if (!in_array($diligence->name, ["Cliente Inaceitável", "Reforçada"])) return;

        $description = $this->buildRiskDescription($riskAssessment, $diligence);

        $dateValidate = [
            'entity_id' => $riskAssessment->entity->id,
            'type' => "AML",
            'category' => "KYC",
            'description' => $description,
        ];

        $alert = $this->alertRepository->findByValidate($dateValidate);

        if (!$alert) {
            $alert = $this->alertRepository->store([
                'name'        => $riskAssessment->entity->social_denomination,
                'country'     => $riskAssessment->nationlity->description,
                'birth_date'  => "",
                'level'       => "Alto",
                'from_id'     => "AV#" . $riskAssessment->id,
                'origin_id'   => "AV#" . $riskAssessment->id,
                'entity_id'   => $riskAssessment->entity->id,
                'score'       => $riskAssessment->score,
                'type'        => "AML",
                'category'    => "KYC",
                'list'        => "AML",
                'is_active'   => true,
                'description' => $description ?? 'Desconhecido',
            ]);


            $host = config('app.url');
            SendGrupoAlertEmailJob::dispatch($alert->id, $host)->onQueue('high');
        }
    }

    private function dispatchGenerateAlertsJob(array $data, $riskAssessment): void
    {
        if (!array_key_exists('is_sanctioned', $data) && !array_key_exists('is_pep', $data)) {
            if ($data['pep'] == false || $data['santion'] == false) {
                GenerateAlertsJob::dispatch($riskAssessment->entity->id, $riskAssessment)
                    ->onQueue('high');
            }
        }
    }

    private function loadRelations($riskAssessment): void
    {
        $riskAssessment->load($this->relationships);
    }
    private function calculateTotalScore($riskAssessment, $totalRiskProduct, $formula, $beneficialOwnerScore): float
    {
        // Garante que as relações estão carregadas
        $riskAssessment->loadMissing(['indetificationCapacity', 'profession', 'nationlity', 'countryResidence', 'category', 'channel']);

        $baseScores = [
            'identification'   => $riskAssessment->indetificationCapacity?->score ?? 0,
            'profession'       => $riskAssessment->profession?->score ?? 0,
            'nationality'      => $riskAssessment->nationlity?->score ?? 0,
            'countryResidence' => $riskAssessment->countryResidence?->score ?? 0,
            'statusResidence'  => ($riskAssessment->status_residence == StatusResidence::RESIDENTE) ? 1 : 3,
            'formEstablishment' => ($riskAssessment->form_establishment == FormEstablishment::PRESENCIAL) ? 1 : 3,
            'processesReported' => $riskAssessment->processesReportedAuthoritie ? 3 : 0,
            'santion'          => $riskAssessment->santion ? 1000 : 0,
            'pep'              => $riskAssessment->pep ? 3 : 0,
            'channel'          => $riskAssessment->channel?->score ?? 0,
            'category'         => $riskAssessment->category?->score ?? 0, // <- seguro agora
            'totalRiskProduct' => (float)($totalRiskProduct ?? 0),
            'form_establishment' => $riskAssessment->form_establishment?->score ?? 0,
        ];

        $safeFormula = fn($field) => (float)($formula->$field ?? 0);
        $safeBeneficial = (float)($beneficialOwnerScore ?? 0);

        $total = 0;

        if (($formula->entity_type ?? null) == 2) {
            // Entidade Particular
            $total += $baseScores['identification']   * $safeFormula('identification_capacity');
            $total += $baseScores['profession']       * $safeFormula('profession');
            $total += $baseScores['nationality']      * $safeFormula('nationality');
            $total += $baseScores['countryResidence'] * $safeFormula('country_residence');
            $total += $baseScores['statusResidence']  * $safeFormula('status_residence');
            $total += $baseScores['processesReported'] * $safeFormula('processesReportedAuthoritie');
            $total += $baseScores['totalRiskProduct'] * $safeFormula('product_risk');
            $total += $baseScores['santion']          * $safeFormula('santion');
            $total += $baseScores['pep']              * $safeFormula('pep');
            $total += $baseScores['channel']          * $safeFormula('channel');
        } else {
            // Entidade Coletiva
            $total += $baseScores['identification']   * $safeFormula('identification_capacity');
            $total += $baseScores['category']         * $safeFormula('category');
            $total += $baseScores['form_establishment'] * $safeFormula('profession');
            $total += $baseScores['countryResidence'] * $safeFormula('country_residence');
            $total += $baseScores['statusResidence']  * $safeFormula('status_residence');
            $total += $safeBeneficial                 * $safeFormula('beneficialOwner');
            $total += $baseScores['totalRiskProduct'] * $safeFormula('product_risk');
            $total += $baseScores['processesReported'] * $safeFormula('processesReportedAuthoritie');
            $total += $baseScores['santion']          * $safeFormula('santion');
            $total += $baseScores['channel']          * $safeFormula('channel');
        }

        return $total;
    }


    private function updateEntityRisk($riskAssessment, $total, $diligence, $reassessmentPeriod): void
    {
        $entity = $riskAssessment?->entity();

        $entity->update([
            'risk_level' => $diligence?->risk,
            'diligence' => $diligence?->name,
            'color' => $diligence?->color,
            'reassessmentPeriod' => $reassessmentPeriod,
            'last_evaluation' => now()
        ]);
    }

    public function getTotalRiskLevelByCategory(array $data = []): array
    {
        return $this->repository->totalRiskLevelByCategory($data);
    }

    public function getTotalRiskLevelByProfession(array $data = []): array
    {
        return $this->repository->totalRiskLevelByProfession($data);
    }

    public function getTotalRiskLevelByChannel(array $data = []): array
    {
        return $this->repository->totalRiskLevelByChannel($data);
    }

    public function getTotalRiskLevelByPep(array $data = []): array
    {
        return $this->repository->totalRiskLevelByPep($data);
    }

    public function getTotalRiskLevelByCountryResidence(array $data = []): array
    {
        return $this->repository->totalRiskLevelByCountryResidence($data);
    }

    public function getTotalRiskLevelByNationality(array $data = []): array
    {
        return $this->repository->totalRiskLevelByNationality($data);
    }

    public function getTotalRiskLevelByProductRisk(array $data = []): array
    {
        return $this->repository->totalRiskLevelByProductRisk($data);
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


    public function countRiskPoints(array $data)
    {
        $pepCount = 0;
        $sanctionCount = 0;
        $nationalityScoreTotal = 0;

        foreach ($data['beneficial_owners'] ?? [] as $owner) {

            // PEP
            $pep = is_array($owner) ? ($owner['pep'] ?? false) : ($owner->pep ?? false);

            // SANÇÃO
            $santion = is_array($owner) ? ($owner['santion'] ?? false) : ($owner->santion ?? false);

            if ($pep) {
                $pepCount++;
            }

            if ($santion) {
                $sanctionCount++;
            }

            // NATIONALITY
            $nationality = is_array($owner) ? ($owner['nationality'] ?? null) : ($owner->nationality ?? null);

            if (!empty($nationality)) {
                $indicator = $this->indicatorTypeRepository->getByDescription($nationality);
                $nationalityScoreTotal += $indicator?->score ?? 0;
            }
        }

        $pepPoints = $pepCount * 3;
        $sanctionPoints = $sanctionCount * 20;

        return $pepPoints + $sanctionPoints + $nationalityScoreTotal;
    }

    private function buildRiskDescription($riskAssessment, $diligence): string
    {
        $formula = $riskAssessment->riskFormula;

        $factors = [];

        $factors['País de residência'] =
            ($riskAssessment->countryResidence?->score ?? 0) *
            ($formula->country_residence ?? 0);

        $factors['Canal de distribuição'] =
            ($riskAssessment->channel?->score ?? 0) *
            ($formula->channel ?? 0);

        $factors['Profissão'] =
            ($riskAssessment->profession?->score ?? 0) *
            ($formula->profession ?? 0);

        $factors['Categoria'] =
            ($riskAssessment->category?->score ?? 0) *
            ($formula->category ?? 0);

        $factors['Capacidade de identificação'] =
            ($riskAssessment->indetificationCapacity?->score ?? 0) *
            ($formula->identification_capacity ?? 0);

        if ($riskAssessment->processesReportedAuthoritie) {
            $factors['Processos reportados às autoridades'] = 3;
        }

        if ($riskAssessment->santion) {
            $factors['Entidade sancionada'] = 1000;
        }

        if ($riskAssessment->pep) {
            $factors['Pessoa Politicamente Exposta (PEP)'] = 3;
        }

        arsort($factors);

        $topFactors = array_slice(array_keys($factors), 0, 3);

        $descriptionFactors = implode(', ', $topFactors);

        return "Avaliação de risco resultou em nível de risco {$diligence->risk} e diligência {$diligence->name}. Principais fatores de risco identificados: {$descriptionFactors}.";
    }



    public function is_pep(array $data, $id)
    {
        $alert = $this->alertRepository->show($id);

        $lastAssessment = $this->repository->getByEntityId($alert->entity_id);

        $products = $this->productRiskService->showProduct($lastAssessment->id);

        $data['product_risk'] = $products
            ? $products->pluck('product_id')->toArray()
            : $data['product_risk'] ?? [];

        // Inicializa dados mínimos

        if (array_key_exists('is_pep', $data)) {
            $data['pep'] = (bool) $data['is_pep'];
            $data['is_pep'] = (bool) $data['is_pep'];
        }

        if (array_key_exists('is_sanctioned', $data)) {
            $data['santion'] = (bool) $data['is_sanctioned'];
            $data['is_sanctioned'] = (bool) $data['is_sanctioned'];
        }

        if (array_key_exists('is_reported', $data)) {
            $data['processesReportedAuthoritie'] = (bool) $data['is_reported'];
        }

        $data['entity_id'] = $alert->entity_id;

        $data['risk_assessment'] = $lastAssessment->risk_assessment;
        if ($lastAssessment) {
            // Copia os dados do último assessment e sobrescreve com os novos dados
            $data = array_merge($lastAssessment->toArray(), $data);
            unset($data['id']); // Remove o ID para criar um novo registro
            unset($data['created_at'], $data['updated_at']); // Remove timestamps
        }

        // Chama o método store existente para criar a nova avaliação
        return $this->store($data);
    }
}
