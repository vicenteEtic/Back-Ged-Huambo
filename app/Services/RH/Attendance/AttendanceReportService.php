<?php

namespace App\Services\RH\Attendance;

use App\Models\RH\Employee\Employee;
use App\Models\RH\Attendance\Attendance;
use App\Models\RH\Leave\Holiday;
use App\Models\User\User;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;

class AttendanceReportService
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    /**
     * Dados do relatório respeitando o sistema de filtros da listagem
     * (period/date/start_date+end_date/employee_id).
     */
    public function data(array $filters, ?int $employeeId = null): array
    {
        if ($employeeId) {
            $raw = $this->attendanceService->employeeAssiduidade($employeeId, $filters);

            return [
                'employee' => $raw['employee'],
                'records' => $raw['records'],
                'summary' => $raw['summary'] ?? $this->emptySummary(),
                'filters' => [
                    'period' => $raw['period']['period'],
                    'start_date' => $raw['period']['start_date'],
                    'end_date' => $raw['period']['end_date'],
                ],
            ];
        }

        return $this->attendanceService->attendanceListing($filters);
    }

    public function render(array $filters, ?int $employeeId = null, ?User $generatedBy = null): string
    {
        $filters = array_merge(['period' => 'today'], $filters);
        $data = $this->data($filters, $employeeId);

        $employee = null;

        if ($employeeId) {
            $department = Employee::find($employeeId)?->department?->name;

            $employee = [
                'name' => $data['employee']['full_name'],
                'employee_number' => $data['employee']['employee_number'],
                'department' => $department,
            ];
        }

        $html = view('rh.attendance.report', [
            'records' => $data['records'],
            'listing' => [
                'summary' => $data['summary'],
                'filters' => $data['filters'],
            ],
            'periodLabel' => $this->periodLabel($filters),
            'employee' => $employee,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'generatedBy' => $generatedBy?->name ?? ($generatedBy?->username ?? 'Sistema'),
            'appName' => config('app.name'),
        ])->render();

        $options = new Options;
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Times');
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }

    public function fileName(array $filters, ?int $employeeId = null): string
    {
        $period = $filters['period'] ?? ($employeeId ? 'assiduidade' : 'periodo');

        return 'Relatorio_Pontualidade_Assiduidade_'.str_replace([' ', '/'], '_', strtoupper($period)).'.pdf';
    }

    /**
     * Prepara o mapa mensal de efectividade do pessoal.
     * Cada linha representa um funcionário activo, mesmo sem faltas registadas.
     */
    public function effectivenessMap(int $year, int $month, ?int $departmentId = null, ?int $gabineteId = null): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $workingDays = $this->workingDays($start, $end);

        $absenceCodes = [
            'unjustified' => ['injustificada', 'unjustified'],
            'article_65' => ['artigo_65', 'art65', 'lei_65'],
            'article_66' => ['artigo_66', 'art66', 'lei_66'],
            'article_67' => ['artigo_67', 'art67', 'lei_67'],
            'article_68' => ['artigo_68', 'art68', 'lei_68'],
            'sickness' => ['doenca', 'doença', 'licenca_doenca', 'licenca_medica'],
            'marriage' => ['casamento'],
            'childbirth' => ['parto', 'maternidade', 'paternidade'],
            'disciplinary' => ['disciplinar'],
            'registered' => ['registada', 'censura_registada'],
            'called' => ['chamada'],
        ];

        $records = Attendance::query()
            ->where('status', 'absent')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['employee_id', 'absence_type', 'is_justified']);

        $employeesQuery = Employee::query()
            ->with(['careerCategory', 'position', 'department'])
            ->where('status', 'active')
            ->whereNotIn('department_id', \App\Support\PontoExceptions::exemptDepartmentIds());

        if ($departmentId) {
            $employeesQuery->where('department_id', $departmentId);
        }

        if ($gabineteId) {
            $employeesQuery->whereHas('department', function ($query) use ($gabineteId) {
                $query->whereKey($gabineteId)->where('type', 'gabinete');
            });
        }

        $employees = $employeesQuery->orderBy('full_name')->get();

        $rows = $employees->map(function (Employee $employee) use ($records, $absenceCodes, $workingDays) {
            $employeeRecords = $records->where('employee_id', $employee->id);
            $counts = array_fill_keys([...array_keys($absenceCodes), 'other'], 0);

            foreach ($employeeRecords as $record) {
                $code = strtolower((string) $record->absence_type);
                $matched = false;
                foreach ($absenceCodes as $column => $codes) {
                    if (in_array($code, $codes, true)) {
                        $counts[$column]++;
                        $matched = true;
                        break;
                    }
                }

                if (! $matched && (bool) $record->is_justified === false) {
                    $counts['unjustified']++;
                } elseif (! $matched) {
                    $counts['other']++;
                }
            }

            $totalAbsences = $employeeRecords->count();

            return array_merge([
                'employee_number' => $employee->employee_number,
                'full_name' => $employee->full_name,
                'category' => $employee->careerCategory?->name ?? $employee->position?->name ?? '-',
                'department' => $employee->department?->name ?? '-',
            ], $counts, [
                'total_absences' => $totalAbsences,
                'effective_days' => max($workingDays - $totalAbsences, 0),
            ]);
        })->values();

        return [
            'year' => $year,
            'month' => $month,
            'month_name' => $this->monthName($month),
            'month_end' => $end->format('d'),
            'working_days' => $workingDays,
            'department_id' => $departmentId,
            'gabinete_id' => $gabineteId,
            'department_name' => $employees->first()?->department?->name,
            'rows' => $rows,
        ];
    }

    public function renderEffectivenessMap(int $year, int $month, ?int $departmentId = null, ?int $gabineteId = null, ?User $generatedBy = null): string
    {
        $data = $this->effectivenessMap($year, $month, $departmentId, $gabineteId);
        // Dompdf não garante suporte a WebP; usa a cópia PNG para o PDF.
        $logoPath = public_path('Emblem_of_Angola.png');
        $logo = is_file($logoPath)
            ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath))
            : null;
        $html = view('rh.attendance.effectiveness-map', array_merge($data, [
            'logo' => $logo,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'generatedBy' => $generatedBy?->name ?? ($generatedBy?->username ?? 'Sistema'),
            'appName' => config('app.name'),
        ]))->render();

        $options = new Options;
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Times');
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A3', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }

    public function effectivenessMapFileName(int $year, int $month): string
    {
        return sprintf('Mapa_Efectividade_%02d-%d.pdf', $month, $year);
    }

    public function renderEffectivenessMapExcel(int $year, int $month, ?int $departmentId = null, ?int $gabineteId = null): string
    {
        $data = $this->effectivenessMap($year, $month, $departmentId, $gabineteId);
        $header = [
            'Item', 'N.º do Agente', 'Nome Completo', 'Categoria',
            'Injustificadas', 'Artigo n.º 65', 'Artigo n.º 66', 'Artigo n.º 67', 'Artigo n.º 68',
            'Licença p/ Doença', 'Licença de Casamento', 'Licença p/ Parto', 'Licença Disciplinar',
            'Licença Registada', 'Licença Chamada', 'Outras', 'Total de faltas', 'Dias de efectividade',
        ];

        $title = 'MAPA DE EFECTIVIDADE DO PESSOAL - '.$data['month_name'].'/'.$year;
        $rows = [
            [$title],
            ['Departamento/Gabinete: '.($data['department_name'] ?: 'Todos')],
            ['Item', '«1»', '«2»', '«3»', '«4»', '', '', '', '', '«5»', '', '', '', '', '', '', 'Total de faltas', 'Dias de efectividade'],
            $header,
        ];

        foreach ($data['rows'] as $index => $row) {
            $rows[] = [
                $index + 1, $row['employee_number'], $row['full_name'], $row['category'],
                $row['unjustified'], $row['article_65'], $row['article_66'], $row['article_67'], $row['article_68'],
                $row['sickness'], $row['marriage'], $row['childbirth'], $row['disciplinary'],
                $row['registered'], $row['called'], $row['other'], $row['total_absences'], $row['effective_days'],
            ];
        }

        $path = tempnam(sys_get_temp_dir(), 'mapa_efectividade_xlsx_');
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $this->xlsxContentTypes());
        $zip->addFromString('_rels/.rels', $this->xlsxRootRelationships());
        $zip->addFromString('xl/workbook.xml', $this->xlsxWorkbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xlsxWorkbookRelationships());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->xlsxWorksheet($rows));
        $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', $this->xlsxWorksheetRelationships());
        $zip->addFromString('xl/drawings/drawing1.xml', $this->xlsxDrawing());
        $zip->addFromString('xl/drawings/_rels/drawing1.xml.rels', $this->xlsxDrawingRelationships());
        $logoPath = public_path('Emblem_of_Angola.png');
        if (is_file($logoPath)) {
            $zip->addFile($logoPath, 'xl/media/image1.png');
        }
        $zip->close();

        $content = (string) file_get_contents($path);
        unlink($path);

        return $content;
    }

    public function effectivenessMapExcelFileName(int $year, int $month): string
    {
        return sprintf('Mapa_Efectividade_%02d-%d.xlsx', $month, $year);
    }

    private function xlsxContentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Default Extension="png" ContentType="image/png"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/></Types>';
    }

    private function xlsxRootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    }

    private function xlsxWorkbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Mapa de Efectividade" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private function xlsxWorkbookRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>';
    }

    private function xlsxWorksheet(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheetData>';

        foreach ($rows as $rowIndex => $row) {
            $xml .= '<row r="'.($rowIndex + 1).'">';
            foreach (array_values($row) as $columnIndex => $value) {
                $column = '';
                $number = $columnIndex + 1;
                while ($number > 0) {
                    $remainder = ($number - 1) % 26;
                    $column = chr(65 + $remainder).$column;
                    $number = intdiv($number - 1, 26);
                }
                $escaped = htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $xml .= '<c r="'.$column.($rowIndex + 1).'" t="inlineStr"><is><t>'.$escaped.'</t></is></c>';
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData><mergeCells count="10"><mergeCell ref="A1:R1"/><mergeCell ref="A2:R2"/><mergeCell ref="A3:A4"/><mergeCell ref="B3:B4"/><mergeCell ref="C3:C4"/><mergeCell ref="D3:D4"/><mergeCell ref="E3:I3"/><mergeCell ref="J3:P3"/><mergeCell ref="Q3:Q4"/><mergeCell ref="R3:R4"/></mergeCells><drawing r:id="rId1"/></worksheet>';

        return $xml;
    }

    private function xlsxWorksheetRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/></Relationships>';
    }

    private function xlsxDrawing(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><xdr:oneCellAnchor><xdr:from><xdr:col>7</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>0</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:from><xdr:ext cx="1100000" cy="1100000"/><xdr:pic><xdr:nvPicPr><xdr:cNvPr id="1" name="Emblema de Angola"/><xdr:cNvPicPr/></xdr:nvPicPr><xdr:blipFill><a:blip r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill><xdr:spPr><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr></xdr:pic><xdr:clientData/></xdr:oneCellAnchor></xdr:wsDr>';
    }

    private function xlsxDrawingRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/image1.png"/></Relationships>';
    }

    private function workingDays(Carbon $start, Carbon $end): int
    {
        $holidays = Holiday::query()
            ->where('is_active', true)
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                    ->orWhere('recurrent', true);
            })
            ->get(['date', 'recurrent'])
            ->filter(function (Holiday $holiday) use ($start) {
                $date = Carbon::parse($holiday->date);

                return $holiday->recurrent
                    ? $date->month === $start->month
                    : true;
            })
            ->map(function (Holiday $holiday) use ($start) {
                $date = Carbon::parse($holiday->date);

                return ($holiday->recurrent ? $start->year : $date->year).'-'.$date->format('m-d');
            })
            ->all();
        $days = 0;
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $holidayKey = $day->year.'-'.$day->format('m-d');
            if (! $day->isWeekend() && ! in_array($holidayKey, $holidays, true)) {
                $days++;
            }
        }

        return $days;
    }

    private function monthName(int $month): string
    {
        return [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'][$month];
    }

    protected function periodLabel(array $filters): string
    {
        $period = $filters['period'] ?? null;

        if ($period && isset(AttendanceService::PERIODS[$period])) {
            return AttendanceService::PERIODS[$period];
        }

        if (! empty($filters['date'])) {
            return 'Dia '.Carbon::parse($filters['date'])->format('d/m/Y');
        }

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            return Carbon::parse($filters['start_date'])->format('d/m/Y').' a '.Carbon::parse($filters['end_date'])->format('d/m/Y');
        }

        return 'Período seleccionado';
    }

    protected function emptySummary(): array
    {
        return [
            'total_records' => 0,
            'present' => 0,
            'late' => 0,
            'absent' => 0,
            'dispensado' => 0,
            'justified_absences' => 0,
            'unjustified_absences' => 0,
            'total_hours_worked' => 0,
            'employees_count' => 0,
            'days_in_period' => 0,
            'working_days' => 0,
        ];
    }
}
