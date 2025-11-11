<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Sale;
use App\Models\PosOrder;
use App\Models\PosConfig;
use App\Models\Company;
use App\Models\Tax;

class GreenterInvoiceService
{
    protected string $url;

    public function __construct()
    {
        $this->url = env('GREENTER_API_URL', 'http://greenter.test/api/');
    }


    protected function resolveTokenForSale(Sale $sale): string
    {
        // Resolver por la empresa asociada a la venta
        $company = null;
        if (!empty($sale->company_id)) {
            $company = Company::query()->with('sunatConnection')->find($sale->company_id);
        }
        // Fallback: primera compañía si la venta no tiene company_id o no existe la empresa
        if (!$company) {
            $company = Company::query()->with('sunatConnection')->orderBy('id')->first();
        }
        $conn = $company?->sunatConnection;
        // Exigir estrictamente token_ikoodev; si está vacío, devolver cadena vacía
        $token = (string) ($conn?->token_ikoodev ?? '');
        return trim($token);
    }

    // buildStaticPayload y sendStaticInvoice fueron removidos; ahora el payload se construye desde la venta.

    /**
     * Construir payload tomando datos del Sale (cliente y journal).
     * Solo mapea los campos solicitados: client.tipoDoc/numDoc/rznSocial y
     * tipoDoc (comprobante), serie y correlativo desde Journal/Sale.
     */
    public function buildPayloadFromSale(Sale $sale): array
    {
        $sale->loadMissing(['customer.identity', 'journal', 'variants.product', 'variants.attributeValues']);

        $docType = (string) ($sale->journal->document_type_code ?? '01');
        $isPos = !empty($sale->pos_order_id);
        $tax = $isPos ? $this->getTaxConfig($sale) : null;
        // Usar cálculo por línea para construir detalles y totales
        $totals = $this->buildDetailsAndTotalsPerLine($sale);
        // Incluir nombres geográficos (departamento, provincia, distrito) en address
        $company = $this->buildCompanyPayload($sale, true);
        $client = $this->buildClientPayload($sale);

        // Payload mínimo solicitado (sin totales de encabezado)
        $payload = [
            'tipoDoc' => $docType,
            'tipoOperacion' => '0101',
            'serie' => (string)($sale->serie ?? ''),
            'correlativo' => (string)($sale->correlative ?? ''),
            'fechaEmision' => ($sale->date ?? now())
                ->setTimezone('America/Lima')
                ->format('Y-m-d\TH:i:sP'),
            'formaPago' => [
                'moneda' => 'PEN',
                'tipo' => 'Contado',
            ],
            'tipoMoneda' => 'PEN',
            'company' => $company,
            'client' => $client,
            'details' => $totals['details'],
        ];

        // === Notas de Crédito/Débito (07/08) ===
        if (in_array($docType, ['07', '08'], true)) {
            ['type' => $affectedType, 'number' => $affectedNumber] = $this->resolveAffectedDocument($sale);
            $payload['tipDocAfectado'] = $affectedType;
            $payload['numDocAfectado'] = $affectedNumber;
            $payload['numDocfectado'] = $affectedNumber;
            if ($docType === '07') {
                [$code, $label] = $this->resolveCreditNoteReason($sale);
                $payload['codMotivo'] = $code;
                $payload['desMotivo'] = $label;
            } else {
                // Nota de Débito: por defecto AUMENTO EN EL VALOR (02)
                $payload['codMotivo'] = '02';
                $payload['desMotivo'] = 'AUMENTO EN EL VALOR';
            }
        }

        return $payload;
    }

    /**
     * Resolver tipo y número del documento AFECTADO para NC/ND.
     * Navega hacia la venta original hasta encontrar un comprobante base (01/03).
     */
    private function resolveAffectedDocument(Sale $sale): array
    {
        $sale->loadMissing(['originalSale.journal']);
        $origin = $sale->originalSale ?: $sale;
        // Subir hasta encontrar un doc base 01/03
        $guard = 0;
        while ($guard < 3) {
            $docType = (string) (optional($origin->journal)->document_type_code ?? '');
            if (in_array($docType, ['01', '03'], true)) {
                break;
            }
            if (! $origin->originalSale) {
                break;
            }
            $origin = $origin->originalSale;
            $guard++;
        }

        $type = (string) (optional($origin->journal)->document_type_code ?? '');
        if (! in_array($type, ['01', '03'], true)) {
            $serieGuess = (string) ($origin->serie ?? '');
            $type = str_starts_with($serieGuess, 'F') ? '01' : (str_starts_with($serieGuess, 'B') ? '03' : '01');
        }

        $number = '';
        $serie = (string) ($origin->serie ?? '');
        $corr = (string) ($origin->correlative ?? '');
        if ($serie !== '' && $corr !== '') {
            $number = $serie . '-' . $corr;
        } else {
            $origSerie = (string) ($sale->original_serie ?? '');
            $origCorr = (string) ($sale->original_correlative ?? '');
            if ($origSerie !== '' && $origCorr !== '') {
                $number = $origSerie . '-' . $origCorr;
            }
        }

        return ['type' => $type, 'number' => $number];
    }

    /**
     * Construir payload ESTÁTICO para Notas de Crédito/Débito (07/08).
     * Usa los valores proporcionados en el ejemplo, con alias 'items'.
     */
    public function buildNotePayloadFromSale(Sale $sale, string $docType): array
    {
        $sale->loadMissing(['customer.identity', 'journal', 'variants.product', 'variants.attributeValues']);

        $tax = $this->getTaxConfig($sale);
        $totals = $this->buildDetailsAndTotals($sale, $tax);
        $company = $this->buildCompanyPayload($sale, true);
        $client = $this->buildClientPayload($sale);

        ['type' => $affectedType, 'number' => $affectedNumber] = $this->resolveAffectedDocument($sale);

        $payload = [
            'tipoDoc' => $docType,
            'serie' => (string) ($sale->serie ?? ''),
            'correlativo' => (string) ($sale->correlative ?? ''),
            'fechaEmision' => ($sale->date ?? now())
                ->setTimezone('America/Lima')
                ->format('Y-m-d\TH:i:sP'),
            'tipDocAfectado' => $affectedType,
            'numDocAfectado' => $affectedNumber,
            'numDocfectado' => $affectedNumber,
            'formaPago' => [
                'moneda' => 'PEN',
                'tipo' => 'Contado',
            ],
            'tipoMoneda' => 'PEN',
            'company' => $company,
            'client' => $client,
            'details' => $totals['details'],
            'mtoIGV' => $totals['igvTotal'],
            'totalImpuestos' => $totals['igvTotal'],
            'valorVenta' => $totals['baseTotal'],
            'subTotal' => $totals['subTotal'],
            'mtoImpVenta' => $totals['subTotal'],
        ];

        if ($docType === '07') {
            [$code, $label] = $this->resolveCreditNoteReason($sale);
            $payload['codMotivo'] = $code;
            $payload['desMotivo'] = $label;
        } else {
            $payload['codMotivo'] = '02';
            $payload['desMotivo'] = 'AUMENTO EN EL VALOR';
        }

        return $payload;
    }

    /**
     * Determinar motivo SUNAT para Nota de Crédito (07) según cantidades devueltas.
     * - 06: DEVOLUCIÓN TOTAL (todas las líneas devueltas con mismas cantidades).
     * - 07: DEVOLUCIÓN POR ÍTEM (devolución parcial o por línea).
     * Fallback: 01 (ANULACION DE LA OPERACION) si no se puede determinar.
     */
    private function resolveCreditNoteReason(Sale $sale): array
    {
        try {
            $sale->loadMissing(['originalSale.variants']);
            $orig = $sale->originalSale;
            if (!$orig) {
                return ['01', 'ANULACION DE LA OPERACION'];
            }
            $origMap = [];
            foreach ($orig->variants as $v) {
                $origMap[(string) $v->id] = (int) ($v->pivot->quantity ?? 0);
            }
            $noteMap = [];
            foreach ($sale->variants as $v) {
                $noteMap[(string) $v->id] = (int) ($v->pivot->quantity ?? 0);
            }

            if (empty($origMap) || empty($noteMap)) {
                return ['01', 'ANULACION DE LA OPERACION'];
            }

            $isFull = (count($origMap) === count($noteMap));
            foreach ($origMap as $id => $qty) {
                if (!isset($noteMap[$id]) || $noteMap[$id] !== $qty) {
                    $isFull = false;
                    break;
                }
            }

            if ($isFull) {
                return ['06', 'DEVOLUCION TOTAL'];
            }
            return ['07', 'DEVOLUCION POR ITEM'];
        } catch (\Throwable $e) {
            return ['01', 'ANULACION DE LA OPERACION'];
        }
    }

    /**
     * Enviar a Greenter usando datos del Sale.
     */
    public function sendInvoiceFromSale(Sale $sale): bool
    {
        try {
            // ——— Gate: solo enviar si el diario es fiscal y tipoDoc válido
            $journal = $sale->journal;
            $isFiscal = (bool) (optional($journal)->is_fiscal ?? false);
            $docType = (string) (optional($journal)->document_type_code ?? '');
            $validDoc = in_array($docType, ['01', '03', '07', '08'], true);
            if (! $isFiscal || ! $validDoc) {
                Log::info('Greenter: skipping send, non-fiscal or invalid doc type', [
                    'sale_id' => $sale->id,
                    'journal_id' => optional($journal)->id,
                    'is_fiscal' => $isFiscal,
                    'document_type_code' => $docType,
                ]);
                try {
                    $sale->sunat_status = 'skipped';
                    $sale->sunat_response = [
                        'http_status' => null,
                        'accepted' => false,
                        'error' => $isFiscal ? 'Documento SUNAT no soportado' : 'Diario no fiscal: sin envío SUNAT',
                        'updated_at' => now()->toIso8601String(),
                    ];
                    $sale->save();
                } catch (\Throwable $e) {
                    Log::warning('No se pudo marcar como skipped', [
                        'sale_id' => $sale->id,
                        'error' => $e->getMessage(),
                    ]);
                }
                return true;
            }

            // Evitar reenvío si ya fue aceptada previamente
            $alreadyAccepted = (bool) (data_get($sale->sunat_response, 'accepted') === true
                || ($sale->sunat_status === 'accepted'));
            if ($alreadyAccepted) {
                Log::info('Greenter: skipping send, sale already accepted', [
                    'sale_id' => $sale->id,
                    'sunat_status' => $sale->sunat_status,
                    'sunat_response.accepted' => data_get($sale->sunat_response, 'accepted'),
                ]);
                return true;
            }
            // Resolver token temprano para ahorrar procesos
            $dynamicToken = $this->resolveTokenForSale($sale);
            if (empty($dynamicToken)) {
                Log::warning('SUNAT token_ikoodev vacío; omitiendo envío', [
                    'sale_id' => $sale->id,
                    'company_id' => $sale->company_id,
                    'journal_id' => $sale->journal_id,
                ]);
                try {
                    $sale->sunat_status = 'skipped';
                    $sale->sunat_response = [
                        'reason' => 'missing_ikoodev_token',
                        'message' => 'No está configurado token_ikoodev en SunatConnection.',
                        'updated_at' => now()->toIso8601String(),
                    ];
                    $sale->save();
                } catch (\Throwable $e) {
                    Log::warning('No se pudo marcar sale como skipped por token_ikoodev faltante', [
                        'sale_id' => $sale->id,
                        'error' => $e->getMessage(),
                    ]);
                }
                return true;
            }

            // Marcar como procesando antes de construir/enviar
            try {
                $sale->sunat_status = 'processing';
                $sale->sunat_response = null;
                $sale->save();
            } catch (\Throwable $e) {
                Log::warning('No se pudo actualizar estado SUNAT a processing', [
                    'sale_id' => $sale->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $payload = in_array($docType, ['07', '08'], true)
                ? $this->buildNotePayloadFromSale($sale, $docType)
                : $this->buildPayloadFromSale($sale);

            // Preparar meta de diagnóstico (no se envía)
            $calcMode = !empty($sale->pos_order_id) ? 'pos_global' : 'sales_per_line';
            $applyTax = null;
            $taxRate = null;
            $pricesIncludeTax = null;
            if ($calcMode === 'pos_global') {
                $tc = $this->getTaxConfig($sale);
                $applyTax = $tc['apply_tax'] ?? null;
                $taxRate = $tc['tax_rate'] ?? null;
                $pricesIncludeTax = $tc['prices_include_tax'] ?? null;
            }

            // Sanitizar payload antes de enviar/guardar (remover items/meta)
            $outPayload = $payload;
            unset($outPayload['items'], $outPayload['meta']);

            // Guardar payload sanitizado para depuración
            try {
                Storage::disk('local')->put(
                    'greenter_outbox/sale-' . $sale->id . '-payload.json',
                    json_encode($outPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                );
            } catch (\Throwable $e) {
                Log::warning('No se pudo guardar payload de Greenter', [
                    'sale_id' => $sale->id,
                    'error' => $e->getMessage(),
                ]);
            }
            Log::info('Greenter payload preview', [
                'sale_id' => $sale->id,
                'details_count' => is_array($outPayload['details'] ?? null) ? count($outPayload['details']) : 0,
                'first_detail' => $outPayload['details'][0] ?? null,
                'first_tipAfeIgv' => $outPayload['details'][0]['tipAfeIgv'] ?? null,
                'serie' => $outPayload['serie'] ?? null,
                'correlativo' => $outPayload['correlativo'] ?? null,
                'tipoDoc' => $outPayload['tipoDoc'] ?? null,
                'calc_mode' => $calcMode,
                'apply_tax' => $applyTax,
                'tax_rate' => $taxRate,
                'prices_include_tax' => $pricesIncludeTax,
            ]);
            // Elegir endpoint según tipo de documento
            $base = rtrim((string) $this->url, '/') . '/';
            $endpoint = in_array($docType, ['07', '08'], true) ? 'notes/send' : 'invoices/send';
            $requestUrl = $base . $endpoint;

            $response = Http::withToken($dynamicToken)
                ->acceptJson()
                ->post($requestUrl, $outPayload);

            // Guardar respuesta para depuración
            try {
                Storage::disk('local')->put(
                    'greenter_outbox/sale-' . $sale->id . '-response.json',
                    json_encode([
                        'status' => $response->status(),
                        'body' => $response->json() ?? $response->body(),
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                );
            } catch (\Throwable $e) {
                Log::warning('No se pudo guardar respuesta de Greenter', [
                    'sale_id' => $sale->id,
                    'error' => $e->getMessage(),
                ]);
            }

            if ($response->successful()) {
                Log::info('Greenter invoice sent (from sale)', [
                    'sale_id' => $sale->id,
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
                // Actualizar estado según respuesta del CDR si disponible
                $json = $response->json();
                // Detectar paths comunes del CDR/success
                $accepted = false;
                try {
                    $accepted = (bool) (data_get($json, 'body.response.SunatResponse.success') === true
                        || (int) data_get($json, 'body.response.SunatResponse.cdrResponse.code') === 0
                        || (int) data_get($json, 'response.SunatResponse.cdrResponse.code') === 0
                        || (int) data_get($json, 'cdrResponse.code') === 0
                        || (int) data_get($json, 'data.cdrResponse.code') === 0);
                } catch (\Throwable $e) {
                    // Ignore parsing errors
                }
                $sunatStatus = $accepted ? 'accepted' : 'sent';
                // Construir respuesta simplificada para BD
                $simplified = [
                    'http_status' => $response->status(),
                    'accepted' => $accepted,
                    'cdr_code' => data_get($json, 'body.response.SunatResponse.cdrResponse.code')
                        ?? data_get($json, 'response.SunatResponse.cdrResponse.code')
                        ?? data_get($json, 'cdrResponse.code')
                        ?? data_get($json, 'data.cdrResponse.code'),
                    'cdr_description' => data_get($json, 'body.response.SunatResponse.cdrResponse.description')
                        ?? data_get($json, 'response.SunatResponse.cdrResponse.description')
                        ?? data_get($json, 'cdrResponse.description')
                        ?? data_get($json, 'data.cdrResponse.description'),
                    'cdr_notes' => data_get($json, 'body.response.SunatResponse.cdrResponse.notes')
                        ?? data_get($json, 'response.SunatResponse.cdrResponse.notes')
                        ?? data_get($json, 'cdrResponse.notes')
                        ?? data_get($json, 'data.cdrResponse.notes'),
                    'document_id' => data_get($json, 'body.response.document_id')
                        ?? data_get($json, 'response.document_id'),
                    'hash' => data_get($json, 'body.response.hash')
                        ?? data_get($json, 'response.hash'),
                    'updated_at' => now()->toIso8601String(),
                ];
                try {
                    $sale->sunat_status = $sunatStatus;
                    $sale->sunat_response = $simplified;
                    $sale->save();
                } catch (\Throwable $e) {
                    Log::warning('No se pudo actualizar estado/respuesta SUNAT tras éxito', [
                        'sale_id' => $sale->id,
                        'error' => $e->getMessage(),
                    ]);
                }
                return true;
            }

            Log::warning('Greenter invoice failed (from sale)', [
                'sale_id' => $sale->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            try {
                if ($sale->sunat_status !== 'accepted') {
                    $sale->sunat_status = 'error';
                }
                $json = $response->json();
                $sale->sunat_response = [
                    'http_status' => $response->status(),
                    'accepted' => false,
                    'error' => data_get($json, 'message') ?? ($json ?? $response->body()),
                    'updated_at' => now()->toIso8601String(),
                ];
                $sale->save();
            } catch (\Throwable $e) {
                Log::warning('No se pudo actualizar estado/respuesta SUNAT tras fallo', [
                    'sale_id' => $sale->id,
                    'error' => $e->getMessage(),
                ]);
            }
            return false;
        } catch (\Throwable $e) {
            Log::error('Greenter invoice error (from sale)', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);
            try {
                // No sobreescribir "accepted" si un reintento lanza excepción
                if ($sale->sunat_status !== 'accepted') {
                    $sale->sunat_status = 'error';
                }
                $sale->sunat_response = [
                    'http_status' => null,
                    'accepted' => false,
                    'error' => 'Exception: ' . $e->getMessage(),
                    'updated_at' => now()->toIso8601String(),
                ];
                $sale->save();
            } catch (\Throwable $e2) {
                Log::warning('No se pudo actualizar estado/respuesta SUNAT tras excepción', [
                    'sale_id' => $sale->id,
                    'error' => $e2->getMessage(),
                ]);
            }
            return false;
        }
    }

    /**
     * Mapear nombre de identidad a código SUNAT (Greenter) para cliente.
     */
    private function mapIdentityNameToTipoDoc(?string $name): string
    {
        $n = mb_strtolower(trim((string)($name ?? '')));
        return match ($n) {
            'dni' => '1',
            'ruc' => '6',
            'carnet de extranjería', 'ce', 'carnet de extranjeria' => '4',
            'pasaporte' => '7',
            default => '0', // otros / sin documento
        };
    }

    private function getTaxConfig(Sale $sale): array
    {
        $applyTax = true;
        $taxRate = 0.18;
        $pricesIncludeTax = false;

        // 1) Prioridad: tomar configuración desde el POS de la venta
        $cfg = null;
        if (!empty($sale->pos_order_id)) {
            $posOrder = PosOrder::query()->with('posSession.posConfig')->find($sale->pos_order_id);
            $cfg = optional(optional($posOrder)->posSession)->posConfig;
        }

        // 2) Fallback: buscar PosConfig por journal asociado (invoice/receipt)
        if (!$cfg) {
            $journalId = $sale->journal_id;
            $docType = (string) (optional($sale->journal)->document_type_code ?? '');
            if ($journalId) {
                if ($docType === '01') {
                    $cfg = PosConfig::query()->where('invoice_journal_id', $journalId)->first();
                } elseif ($docType === '03') {
                    $cfg = PosConfig::query()->where('receipt_journal_id', $journalId)->first();
                }
            }
        }

        // 3) Fallback: PosConfig activo por empresa
        if (!$cfg) {
            if (!empty($sale->company_id)) {
                $cfg = PosConfig::query()
                    ->where('company_id', $sale->company_id)
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->first();
            }
        }

        // 4) Fallback general: cualquier PosConfig activo
        if (!$cfg) {
            $cfg = PosConfig::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->first();
        }

        if ($cfg) {
            $applyTax = (bool) ($cfg->apply_tax ?? true);
            $taxRate = (float) ($cfg->tax_rate ?? 0.18);
            $pricesIncludeTax = (bool) ($cfg->prices_include_tax ?? false);

            // Si hay impuesto por defecto asociado al POS, tomar tasa y inclusión desde Tax
            try {
                $defaultTax = optional($cfg)->defaultTax;
                if ($defaultTax && (bool) ($defaultTax->is_active ?? false)) {
                    $taxRate = ((float) ($defaultTax->rate_percent ?? 0.0)) / 100.0;
                    $pricesIncludeTax = (bool) ($defaultTax->is_price_inclusive ?? false);
                    // Mantener applyTax como bandera general; si la tasa de Tax es 0, no aplicar
                    if ($taxRate <= 0) {
                        $applyTax = false;
                    }
                }
            } catch (\Throwable $e) {
                // Ignorar errores y continuar con configuración base
            }
        }
        return [
            'apply_tax' => $applyTax,
            'tax_rate' => $taxRate,
            'prices_include_tax' => $pricesIncludeTax,
            'igv_rate' => $applyTax ? $taxRate : 0.0,
        ];
    }

    /**
     * Construye detalles y totales por LÍNEA usando el pivot de la venta (Sales).
     * Respeta rate por línea y determina afectación desde Tax cuando sea posible.
     */
    private function buildDetailsAndTotalsPerLine(Sale $sale): array
    {
        $details = [];
        $baseTotal = 0.0;
        $igvTotal = 0.0;
        $gravadasBase = 0.0;
        $exoneradasBase = 0.0;
        $inafectasBase = 0.0;

        // Pre-cargar impuestos activos para mapear por tasa e inclusión
        $activeTaxes = Tax::query()->where('is_active', true)->get();

        $findTax = function (float $ratePct, bool $inclusive) use ($activeTaxes) {
            $matched = $activeTaxes
                ->first(function ($t) use ($ratePct, $inclusive) {
                    return ((float) $t->rate_percent) === $ratePct
                        && ((bool) ($t->is_price_inclusive ?? false)) === $inclusive
                        && (string) ($t->tax_type ?? 'IGV') === 'IGV';
                });
            if (!$matched) {
                // Fallback: misma tasa sin considerar inclusión
                $matched = $activeTaxes->first(function ($t) use ($ratePct) {
                    return ((float) $t->rate_percent) === $ratePct
                        && (string) ($t->tax_type ?? 'IGV') === 'IGV';
                });
            }
            return $matched; // puede ser null
        };

        foreach ($sale->variants as $variant) {
            $qty = (float) ($variant->pivot->quantity ?? 0);
            $price = (float) ($variant->pivot->price ?? 0);
            $pivotRatePct = (float) ($variant->pivot->tax_rate ?? 0.0);
            $rate = $pivotRatePct > 0 ? ($pivotRatePct / 100.0) : 0.0;
            $pivotBase = round((float) ($variant->pivot->subtotal ?? 0.0), 2);
            $lineTotal = round($qty * $price, 2);

            // Inferir inclusión comparando base calculada vs base guardada
            $inclusiveGuess = false;
            if ($rate > 0) {
                $calcBaseIfInclusive = round($lineTotal / (1 + $rate), 2);
                $inclusiveGuess = abs($calcBaseIfInclusive - $pivotBase) < 0.02; // tolerancia por redondeo
            }

            $taxModel = $findTax($pivotRatePct, $inclusiveGuess);
            $afe = (string) ($taxModel->affectation_type_code ?? '');
            if ($afe === '') {
                $afe = $rate > 0 ? '10' : '21'; // fallback: Gravado 18 / Exonerado 0%
            }

            // Calcular valores unitarios neto/bruto
            if ($inclusiveGuess && $rate > 0) {
                $unitGross = round($price, 2);
                $unitNet = round($price / (1 + $rate), 2);
            } else {
                $unitNet = round($price, 2);
                $unitGross = $rate > 0 ? round($unitNet * (1 + $rate), 2) : $unitNet;
            }

            // IGV por línea solo si afectación gravada (10)
            $igv = ($afe === '10') ? round($pivotBase * $rate, 2) : 0.0;

            // Acumulados por tipo
            if ($afe === '10') {
                $gravadasBase += $pivotBase;
            } elseif ($afe === '31') {
                $inafectasBase += $pivotBase;
            } elseif ($afe === '40') {
                // Exportación (no lo exponemos en payload actual, pero lo sumamos por consistencia)
                // Greenter admite mtoOperExportacion; aquí no lo usamos.
            } else {
                // Exonerado u otros no gravados
                $exoneradasBase += $pivotBase;
            }

            $desc = (string) $variant->fullName;
            $details[] = [
                'tipAfeIgv' => $afe,
                'codProducto' => (string) $variant->barcode,
                'unidad' => 'NIU',
                'descripcion' => $desc,
                'cantidad' => $qty,
                'mtoValorUnitario' => $unitNet,
                'mtoValorVenta' => $pivotBase,
                'mtoBaseIgv' => $pivotBase,
                'porcentajeIgv' => round($pivotRatePct, 2),
                'igv' => $igv,
                'totalImpuestos' => $igv,
                'mtoPrecioUnitario' => $unitGross,
            ];

            $baseTotal += $pivotBase;
            $igvTotal += $igv;
        }

        $baseTotal = round($baseTotal, 2);
        $igvTotal = round($igvTotal, 2);
        $subTotal = round($baseTotal + $igvTotal, 2);

        return [
            'details' => $details,
            'baseTotal' => $baseTotal,
            'igvTotal' => $igvTotal,
            'subTotal' => $subTotal,
            'gravadasBase' => round($gravadasBase, 2),
            'exoneradasBase' => round($exoneradasBase, 2),
            'inafectasBase' => round($inafectasBase, 2),
        ];
    }

    private function buildDetailsAndTotals(Sale $sale, array $tax): array
    {
        $details = [];
        $baseTotal = 0.0;
        $igvTotal = 0.0;
        $igvRate = (float) ($tax['igv_rate'] ?? 0.0);

        foreach ($sale->variants as $variant) {
            $qty = (float) ($variant->pivot->quantity ?? 0);
            $price = (float) ($variant->pivot->price ?? 0);

            if ($igvRate > 0 && ($tax['prices_include_tax'] ?? false)) {
                $unitGross = round($price, 2);
                $unitNet = round($unitGross / (1 + $igvRate), 2);
            } else {
                $unitNet = round($price, 2);
                $unitGross = $igvRate > 0 ? round($unitNet * (1 + $igvRate), 2) : $unitNet;
            }

            $base = round($qty * $unitNet, 2);
            $igv = round($base * $igvRate, 2);
            $desc = (string) $variant->fullName;

            $details[] = [
                'tipAfeIgv' => ($igvRate > 0 ? 10 : 20),
                'codProducto' => (string) $variant->barcode,
                'unidad' => 'NIU',
                'descripcion' => $desc,
                'cantidad' => $qty,
                'mtoValorUnitario' => $unitNet,
                'mtoValorVenta' => $base,
                'mtoBaseIgv' => $base,
                'porcentajeIgv' => round($igvRate * 100, 2),
                'igv' => $igv,
                'totalImpuestos' => $igv,
                'mtoPrecioUnitario' => $unitGross,
            ];

            $baseTotal += $base;
            $igvTotal += $igv;
        }

        $baseTotal = round($baseTotal, 2);
        $igvTotal = round($igvTotal, 2);
        $subTotal = round($baseTotal + $igvTotal, 2);

        return [
            'details' => $details,
            'baseTotal' => $baseTotal,
            'igvTotal' => $igvTotal,
            'subTotal' => $subTotal,
        ];
    }

    private function buildCompanyPayload(Sale $sale, bool $withGeoNames = false): array
    {
        $company = null;
        if (!empty($sale->company_id)) {
            $company = Company::query()
                ->when($withGeoNames, fn($q) => $q->with(['district.province', 'district.department']))
                ->find($sale->company_id);
        }
        if (!$company) {
            $company = Company::query()
                ->when($withGeoNames, fn($q) => $q->with(['district.province', 'district.department']))
                ->orderBy('id')
                ->first();
        }
        $companyRuc = (string) ($company->document_number ?? '');
        $companyRazon = (string) ($company->name ?? '');
        $companyComercial = (string) ($company->trade_name ?? $companyRazon);
        $ubigeo = (string) ($company->district_id ?? '');
        $direccionFiscal = (string) ($company->tax_address ?? $company->address ?? '');

        $address = [
            'ubigueo' => $ubigeo,
            'departamento' => $withGeoNames ? (string) (optional(optional($company)->district)->department->name ?? '') : '',
            'provincia' => $withGeoNames ? (string) (optional(optional($company)->district)->province->name ?? '') : '',
            'distrito' => $withGeoNames ? (string) (optional($company->district)->name ?? '') : '',
            'urbanizacion' => '-',
            'direccion' => $direccionFiscal,
            'codLocal' => '0000',
        ];

        return [
            'ruc' => $companyRuc,
            'razonSocial' => $companyRazon,
            'nombreComercial' => $companyComercial,
            'address' => $address,
        ];
    }

    private function buildClientPayload(Sale $sale): array
    {
        $identityName = optional(optional($sale->customer)->identity)->name;
        $clientTipoDoc = $this->mapIdentityNameToTipoDoc($identityName);
        return [
            'tipoDoc' => $clientTipoDoc,
            'numDoc' => (string) optional($sale->customer)->document_number,
            'rznSocial' => (string) optional($sale->customer)->name,
        ];
    }
}
