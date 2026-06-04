<?php

namespace App\Services\KYT;

use App\External\PepExternalApi;
use App\External\SanctionExternalApi;
use App\Models\Entities\Entities;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PepSanctionCheckService
{
    private const CACHE_TTL = 86400;

    public function checkMultiple(array $names): array
    {
        $findings = [];

        foreach ($names as $name) {
            $name = trim($name ?? '');
            if (empty($name)) continue;

            $result = $this->checkName($name);
            if ($result['found']) {
                $findings[] = $result;
            }
        }

        return $findings;
    }

    public function checkName(string $name): array
    {
        $cacheKey = 'pep_sanction_check_' . md5(strtolower(trim($name)));

        return Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL), function () use ($name) {
            $result = [
                'name' => $name,
                'found' => false,
                'pep' => false,
                'sanction' => false,
                'pep_score' => null,
                'sanction_score' => null,
            ];

            try {
                $pepData = PepExternalApi::getDataPepExternal($name);
                if (!empty($pepData['data'])) {
                    $result['found'] = true;
                    $result['pep'] = true;
                    $result['pep_score'] = $pepData['data'][0]['score'] ?? null;
                }
            } catch (\Throwable $e) {
                Log::warning("PEP API error for {$name}: " . $e->getMessage());
            }

            try {
                $sanctionData = SanctionExternalApi::getDataSanctionExternal($name);
                if (!empty($sanctionData['data'])) {
                    $result['found'] = true;
                    $result['sanction'] = true;
                    $result['sanction_score'] = $sanctionData['data'][0]['score'] ?? null;
                }
            } catch (\Throwable $e) {
                Log::warning("Sanction API error for {$name}: " . $e->getMessage());
            }

            return $result;
        });
    }

    public function buildDescriptionSuffix(array $findings): string
    {
        if (empty($findings)) return '';

        $lines = [];
        foreach ($findings as $f) {
            $parts = [];
            if ($f['pep']) {
                $parts[] = 'lista PEP';
            }
            if ($f['sanction']) {
                $parts[] = 'lista de Sanções';
            }
            $lists = implode(' e ', $parts);
            $lines[] = "⚠️ \"{$f['name']}\" encontrado em {$lists}";
        }

        return "\n\n" . implode("\n", $lines);
    }

    public function extractPayerNames(array $receipts): array
    {
        $names = [];
        foreach ($receipts as $r) {
            $item = is_object($r) ? (array) $r : $r;
            $name = trim($item['nome_pagador'] ?? '');
            if (!empty($name)) {
                $names[] = $name;
            }
        }
        return array_unique($names);
    }

    public function extractBeneficiaryNames(array $beneficiaries): array
    {
        $names = [];
        foreach ($beneficiaries as $b) {
            $item = is_object($b) ? (array) $b : $b;
            $name = trim($item['nome_beneficiario'] ?? '');
            if (!empty($name)) {
                $names[] = $name;
            }
        }
        return array_unique($names);
    }
}
