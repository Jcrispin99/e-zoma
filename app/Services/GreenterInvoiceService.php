<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Sale;
use App\Models\PosOrder;
use App\Models\Company;

class GreenterInvoiceService
{
    protected string $url;
    protected ?string $token;

    public function __construct()
    {
        // Usar URL base desde .env, el endpoint se elige por tipo de documento
        $this->url = config('services.greenter.url') ?? env('GREENTER_API_URL', 'http://greenter.test/api/');
        // Permitir que el token sea null y resolver dinámicamente por venta/empresa
        $this->token = config('services.greenter.token') ?? env('GREENTER_API_TOKEN') ?? null;
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

        // === Configuración de impuestos desde POS (si aplica) ===
        $applyTax = true;
        $taxRate = 0.18; // 18% por defecto
        $pricesIncludeTax = false;

        if (!empty($sale->pos_order_id)) {
            $posOrder = PosOrder::query()->with('posSession.posConfig')->find($sale->pos_order_id);
            $cfg = optional(optional($posOrder)->posSession)->posConfig;
            if ($cfg) {
                $applyTax = (bool) ($cfg->apply_tax ?? true);
                $taxRate = (float) ($cfg->tax_rate ?? 0.18);
                $pricesIncludeTax = (bool) ($cfg->prices_include_tax ?? false);
            }
        }

        $identityName = optional(optional($sale->customer)->identity)->name;
        $clientTipoDoc = $this->mapIdentityNameToTipoDoc($identityName);

        // Calcular detalles desde las variantes de la venta
        $details = [];
        $baseTotal = 0.0;
        $igvTotal = 0.0;
        $igvRate = $applyTax ? $taxRate : 0.0;

        foreach ($sale->variants as $variant) {
            $qty = (float) ($variant->pivot->quantity ?? 0);
            $price = (float) ($variant->pivot->price ?? 0);

            // Determinar precios según configuración
            if ($igvRate > 0 && $pricesIncludeTax) {
                // Precio incluye IGV: neto = bruto / (1 + tasa), bruto = price
                $unitGross = round($price, 2);
                $unitNet = round($unitGross / (1 + $igvRate), 2);
            } else {
                // Precio no incluye IGV, o no se aplica impuesto
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

        $docType = (string) ($sale->journal->document_type_code ?? '01');

        $payload = [
            'ublVersion' => '2.1',
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
            'company' => [
                // Mantener estático por ahora
                'ruc' => 20614550440,
                'razonSocial' => 'KOODI SOLUTIONS S.A.C.',
                'nombreComercial' => 'Ikoo Dev',
                'address' => [
                    'ubigueo' => '100601',
                    'departamento' => 'HUANUCO',
                    'provincia' => 'LEONCIO PRADO',
                    'distrito' => 'RUPA-RUPA',
                    'urbanizacion' => '',
                    'direccion' => 'Jr. Callao Nro. 545',
                    'codLocal' => '0000',
                ],
            ],
            'client' => [
                'tipoDoc' => $clientTipoDoc,
                'numDoc' => (string)optional($sale->customer)->document_number,
                'rznSocial' => (string)optional($sale->customer)->name,
            ],
            // Totales según afectación
            'mtoOperGravadas' => $igvRate > 0 ? $baseTotal : 0,
            'mtoOperExoneradas' => $igvRate > 0 ? 0 : $baseTotal,
            'mtoIGV' => $igvTotal,
            'totalImpuestos' => $igvTotal,
            'valorVenta' => $baseTotal,
            'subTotal' => $subTotal,
            'mtoImpVenta' => $subTotal,
            'details' => $details,
            // Alias para compatibilidad con servicios que esperan 'items'
            'items' => $details,
            // Añadir pista de configuración usada
            'meta' => [
                'apply_tax' => $applyTax,
                'tax_rate' => $taxRate,
                'prices_include_tax' => $pricesIncludeTax,
            ],
        ];

        // === Notas de Crédito/Débito (07/08) ===
        if (in_array($docType, ['07', '08'], true)) {
            // Determinar documento afectado (tipo y número)
            $affectedType = (string) (
                $sale->original_document_type_code
                ?? optional(optional($sale->originalSale)->journal)->document_type_code
                ?? ''
            );
            if ($affectedType === '') {
                $serieGuess = (string) ($sale->original_serie ?? '');
                $affectedType = str_starts_with($serieGuess, 'F') ? '01' : (str_starts_with($serieGuess, 'B') ? '03' : '01');
            }

            $affectedNumber = (string) (trim((string) ($sale->original_serie ?? '')) !== '' && trim((string) ($sale->original_correlative ?? '')) !== ''
                ? ($sale->original_serie . '-' . $sale->original_correlative)
                : (optional($sale->originalSale)->serie && optional($sale->originalSale)->correlative
                    ? (optional($sale->originalSale)->serie . '-' . optional($sale->originalSale)->correlative)
                    : '')
            );

            // Motivo por defecto si no existe en la BD todavía
            if ($docType === '07') {
                $codMotivo = '01';
                $desMotivo = 'ANULACION DE LA OPERACION';
            } else { // '08'
                $codMotivo = '02';
                $desMotivo = 'AUMENTO EN EL VALOR';
            }

            // Agregar campos específicos de NC/ND
            $payload['tipDocAfectado'] = $affectedType;
            $payload['numDocAfectado'] = $affectedNumber;
            // Compatibilidad con algunos payloads que usan la clave con typo
            $payload['numDocfectado'] = $affectedNumber;
            $payload['codMotivo'] = $codMotivo;
            $payload['desMotivo'] = $desMotivo;
        }

        return $payload;
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

            $payload = $this->buildPayloadFromSale($sale);
            // Guardar payload para depuración
            try {
                Storage::disk('local')->put(
                    'greenter_outbox/sale-' . $sale->id . '-payload.json',
                    json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                );
            } catch (\Throwable $e) {
                Log::warning('No se pudo guardar payload de Greenter', [
                    'sale_id' => $sale->id,
                    'error' => $e->getMessage(),
                ]);
            }
            Log::info('Greenter payload preview', [
                'sale_id' => $sale->id,
                'details_count' => is_array($payload['details'] ?? null) ? count($payload['details']) : 0,
                'first_detail' => $payload['details'][0] ?? null,
                'first_tipAfeIgv' => $payload['details'][0]['tipAfeIgv'] ?? null,
                'serie' => $payload['serie'] ?? null,
                'correlativo' => $payload['correlativo'] ?? null,
                'tipoDoc' => $payload['tipoDoc'] ?? null,
                'apply_tax' => $payload['meta']['apply_tax'] ?? null,
                'tax_rate' => $payload['meta']['tax_rate'] ?? null,
                'prices_include_tax' => $payload['meta']['prices_include_tax'] ?? null,
            ]);
            // Elegir endpoint según tipo de documento
            $base = rtrim((string) $this->url, '/') . '/';
            $endpoint = in_array($docType, ['07', '08'], true) ? 'notes/send' : 'invoices/send';
            $requestUrl = $base . $endpoint;

            $response = Http::withToken($dynamicToken)
                ->acceptJson()
                ->post($requestUrl, $payload);

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
                // No sobreescribir "accepted" si un reintento falla
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
}
