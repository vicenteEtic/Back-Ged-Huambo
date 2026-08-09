<?php

namespace App\Services\RH\Declaration;

use App\Models\RH\Declaration\DeclarationRequest;
use App\Support\DeclarationText;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\VerticalJc;

class DeclarationDocxService
{
    private const FONT = 'Times New Roman';

    public function fileName(DeclarationRequest $request): string
    {
        $type = $request->declarationType?->name ?? 'declaracao';
        $number = $request->numero_declaracao ?? $request->issued_number ?? $request->reference_number;

        return 'Declaracao_'.strtoupper(substr($type, 0, 30)).'_'.$number.'.docx';
    }

    public function generate(DeclarationRequest $request): string
    {
        $request->loadMissing('employee', 'declarationType');
        $data = $this->documentData($request);

        $phpWord = new PhpWord;
        $phpWord->getDefaultFontName(self::FONT);
        $phpWord->getDefaultFontSize(11);

        $section = $phpWord->addSection([
            'orientation' => 'portrait',
            'marginTop' => 1000,
            'marginBottom' => 1000,
            'marginLeft' => 1200,
            'marginRight' => 1200,
        ]);

        $this->buildHeader($section);
        $this->buildNumberAndDate($section, $data);
        $this->buildTitle($section, $data['title']);
        $this->buildStatement($section, $data['statement']);
        $this->buildFields($section, $data['fields']);
        $this->buildSignature($section, $data);

        ob_start();
        IOFactory::createWriter($phpWord, 'Word2007')->save('php://output');

        return ob_get_clean();
    }

    protected function documentData(DeclarationRequest $request): array
    {
        $content = $request->content ?? [];
        $dataEmissao = $request->data_emissao ?? $request->issued_at?->toDateString() ?? now()->toDateString();

        return [
            'numero_declaracao' => $request->numero_declaracao ?? $request->issued_number,
            'data_emissao' => $dataEmissao,
            'data_emissao_extenso' => DeclarationText::dateSentence($dataEmissao),
            'title' => $content['title'] ?? '',
            'statement' => $content['statement'] ?? '',
            'fields' => $content['fields'] ?? [],
            'assinante_cargo' => $request->assinante_cargo ?? 'O DIRECTOR',
            'assinante_nome' => $request->assinante_nome ?? '',
        ];
    }

    private function buildHeader($section): void
    {
        $center = ['alignment' => Jc::CENTER];

        $section->addText('REPÚBLICA DE ANGOLA', ['bold' => true, 'size' => 13, 'name' => self::FONT], $center);
        $section->addText('GOVERNO DA PROVÍNCIA DO HUAMBO', ['bold' => true, 'size' => 12, 'name' => self::FONT], $center);
        $section->addText('GABINETE DE RECURSOS HUMANOS', ['bold' => true, 'size' => 12, 'name' => self::FONT], $center);

        $section->addText('', [], [
            'alignment' => Jc::CENTER,
            'borderBottomSize' => 6,
            'borderBottomColor' => '000000',
            'spaceAfter' => 240,
        ]);
    }

    private function buildNumberAndDate($section, array $data): void
    {
        $table = $section->addTable(['cellMargin' => 0]);
        $table->addRow();

        $table->addCell(5000)->addText(
            strtoupper($data['numero_declaracao'] ?? ''),
            ['bold' => true, 'size' => 11, 'name' => self::FONT],
            ['alignment' => Jc::LEFT]
        );

        $table->addCell(5000)->addText(
            ucfirst($data['data_emissao_extenso'] ?? ''),
            ['size' => 11, 'name' => self::FONT],
            ['alignment' => Jc::RIGHT]
        );

        $section->addTextBreak(1);
    }

    private function buildTitle($section, ?string $title): void
    {
        if (empty($title)) {
            return;
        }

        $section->addText(
            strtoupper($title),
            ['bold' => true, 'size' => 12, 'name' => self::FONT, 'underline' => 'single'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 240]
        );
    }

    private function buildStatement($section, ?string $statement): void
    {
        if (empty($statement)) {
            return;
        }

        $section->addText($statement, ['size' => 11, 'name' => self::FONT], [
            'alignment' => Jc::BOTH,
            'lineHeight' => 1.3,
            'spaceAfter' => 240,
        ]);
    }

    private function buildFields($section, array $fields): void
    {
        if (empty($fields)) {
            return;
        }

        $table = $section->addTable(['cellMargin' => 80]);

        foreach ($fields as $label => $value) {
            $table->addRow();
            $table->addCell(3300, ['valign' => VerticalJc::TOP])->addText(
                $label.':',
                ['bold' => true, 'size' => 11, 'name' => self::FONT],
                ['alignment' => Jc::LEFT]
            );
            $table->addCell(6600, ['valign' => VerticalJc::TOP])->addText(
                (string) $value,
                ['size' => 11, 'name' => self::FONT],
                ['alignment' => Jc::LEFT]
            );
        }

        $section->addTextBreak(2);
    }

    private function buildSignature($section, array $data): void
    {
        $section->addText('', [], ['spaceAfter' => 240]);

        $section->addText(
            $data['assinante_cargo'] ?? 'O DIRECTOR',
            ['bold' => true, 'size' => 11, 'name' => self::FONT],
            ['alignment' => Jc::RIGHT, 'spaceBefore' => 600]
        );

        $section->addText(
            $data['assinante_nome'] ?? '',
            ['size' => 11, 'name' => self::FONT],
            ['alignment' => Jc::RIGHT]
        );
    }
}
