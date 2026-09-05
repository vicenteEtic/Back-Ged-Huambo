<?php

namespace App\Services\RH\Attendance;

use App\Models\RH\Attendance\AttendanceRequest;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DespachoPdfService
{
    private function options(): Options
    {
        $options = new Options;
        $options->set('isRemoteEnabled', true);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'Times');
        $options->set('isHtml5ParserEnabled', true);

        return $options;
    }

    public function render(AttendanceRequest $request): string
    {
        $request->loadMissing(['employee', 'employee.department', 'employee.category', 'type', 'documents', 'decidedBy']);

        $html = view('rh.dispensa.despacho', [
            'request' => $request,
            'decision' => $request->despacho_decision,
            'note' => $request->decision_note,
            'appName' => config('app.name'),
            'province' => 'HUAMBO',
            'issuedBy' => $request->decidedBy,
        ])->render();

        $dompdf = new Dompdf($this->options());
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Gera o ficheiro e devolve o caminho relativo ao disco.
     */
    public function generate(AttendanceRequest $request): string
    {
        $content = $this->render($request);

        $fileName = 'Despacho_'.Str::slug($request->request_number).'.pdf';
        $relative = $request->request_number
            ? Str::slug($request->request_number).'/'.$fileName
            : $fileName;

        $path = 'dispensas/despachos/'.$relative;
        Storage::disk('public')->put($path, $content);

        return $path;
    }

    public function download(AttendanceRequest $request)
    {
        return Storage::disk('public')->download($request->despacho_path, basename($request->despacho_path));
    }
}
