<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ApiPeruService
{
    /**
     * Lookup DNI/RUC using provider token and cache for 24 hours.
     * Supports APIs Perú (api.apis.net.pe) and falls back to ApiPeru (apiperu.dev).
     *
     * @param string $docType 'dni' or 'ruc'
     * @param string $doc
     * @return array Decoded provider response
     */
    public function lookup(string $docType, string $doc): array
    {
        $token = optional(optional(Company::query()->first())->sunatConnection)->token_apiperu;
        if (!$token) {
            throw new \RuntimeException('No hay token de ApiPeru/APIS Perú configurado');
        }

        $key = sprintf('apiperu:%s:%s', $docType, $doc);

        return Cache::remember($key, now()->addDay(), function () use ($docType, $doc, $token) {
            // Prefer APIs Perú
            $apisPeruBase = 'https://api.apis.net.pe/v1';
            $apisPeruUrl = $docType === 'dni' ? "$apisPeruBase/dni" : "$apisPeruBase/ruc";

            $res = Http::withToken($token)
                ->acceptJson()
                ->get($apisPeruUrl, ['numero' => $doc]);

            if ($res->ok()) {
                return $res->json();
            }

            // Capture error details from APIs Perú
            $apisError = null;
            try {
                $apisJson = $res->json();
                $apisError = is_array($apisJson) ? ($apisJson['error'] ?? $apisJson['message'] ?? null) : null;
            } catch (\Throwable $e) {
                // ignore non-JSON body
            }

            // Fallback to ApiPeru (apiperu.dev)
            $apiPeruBase = 'https://apiperu.dev/api';
            $apiPeruUrl = $docType === 'dni' ? "$apiPeruBase/dni/$doc" : "$apiPeruBase/ruc/$doc";
            $res2 = Http::withToken($token)
                ->acceptJson()
                ->get($apiPeruUrl);

            if ($res2->failed()) {
                $status1 = $res->status();
                $status2 = $res2->status();
                $prefix = $apisError ? ("APIS Perú: $apisError") : "APIS Perú fallo (status $status1)";
                throw new \RuntimeException($prefix . " | ApiPeru fallo (status $status2)");
            }

            return $res2->json();
        });
    }
}
