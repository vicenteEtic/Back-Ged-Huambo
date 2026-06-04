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
                'pep_list_id' => null,
                'sanction_list_id' => null,
                'pep_datasets' => null,
                'sanction_datasets' => null,
                'pep_country' => null,
                'sanction_country' => null,
            ];

            try {
                $pepData = PepExternalApi::getDataPepExternal($name);
                if (!empty($pepData['data'])) {
                    $first = $pepData['data'][0];
                    $result['found'] = true;
                    $result['pep'] = true;
                    $result['pep_score'] = $first['score'] ?? null;
                    $result['pep_list_id'] = $first['id'] ?? null;
                    $result['pep_datasets'] = $first['datasets'] ?? null;
                    $result['pep_country'] = $first['country'] ?? null;
                }
            } catch (\Throwable $e) {
                Log::warning("PEP API error for {$name}: " . $e->getMessage());
            }

            try {
                $sanctionData = SanctionExternalApi::getDataSanctionExternal($name);
                if (!empty($sanctionData['data'])) {
                    $first = $sanctionData['data'][0];
                    $result['found'] = true;
                    $result['sanction'] = true;
                    $result['sanction_score'] = $first['score'] ?? null;
                    $result['sanction_list_id'] = $first['id'] ?? null;
                    $result['sanction_datasets'] = $first['datasets'] ?? null;
                    $result['sanction_country'] = $first['country'] ?? null;
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
                $listInfo = 'lista PEP';
                if ($f['pep_list_id']) $listInfo .= ' (ID: ' . $f['pep_list_id'] . ')';
                if ($f['pep_country']) $listInfo .= ' - ' . $f['pep_country'];
                if ($f['pep_datasets']) $listInfo .= ' [' . $f['pep_datasets'] . ']';
                $parts[] = $listInfo;
            }
            if ($f['sanction']) {
                $listInfo = 'lista de Sanções';
                if ($f['sanction_list_id']) $listInfo .= ' (ID: ' . $f['sanction_list_id'] . ')';
                if ($f['sanction_country']) $listInfo .= ' - ' . $f['sanction_country'];
                if ($f['sanction_datasets']) $listInfo .= ' [' . $f['sanction_datasets'] . ']';
                $parts[] = $listInfo;
            }
            $lists = implode(' e ', $parts);
            $lines[] = "\"{$f['name']}\" encontrado em {$lists}";
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
