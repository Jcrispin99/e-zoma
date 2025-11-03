<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use App\Models\Company;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('greenter:diagnose {saleId?}', function (?int $saleId = null) {
    // Replicar la lógica del constructor (líneas 17-23)
    $constructorUrl = Config::get('services.greenter.url') ?? env('GREENTER_API_URL', 'http://greenter.test/api/invoices/send');
    $constructorToken = Config::get('services.greenter.token') ?? env('GREENTER_API_TOKEN') ?? null;

    // Resolver token dinámico como hace el servicio
    $company = Company::query()->with('sunatConnection')->orderBy('id')->first();
    $conn = optional($company)->sunatConnection;
    $dbTokenIkoo = optional($conn)->token_ikoodev;
    $dbTokenApiPeru = optional($conn)->token_apiperu;
    $dynamicToken = $dbTokenIkoo ?: $dbTokenApiPeru ?: ($constructorToken ?? '');

    $this->info('Greenter constructor URL: ' . $constructorUrl);
    $this->info('Greenter constructor token: ' . ($constructorToken ?? '[null]'));
    $this->info('DB token (ikoodev): ' . ($dbTokenIkoo ?? '[null]'));
    $this->info('DB token (apiperu): ' . ($dbTokenApiPeru ?? '[null]'));
    $this->info('Token efectivo (dynamicToken): ' . ($dynamicToken !== '' ? $dynamicToken : '[empty]'));

    if ($saleId) {
        $sale = \App\Models\Sale::query()->with('journal')->find($saleId);
        if (!$sale) {
            $this->error('Sale #' . $saleId . ' no encontrada');
        } else {
            $this->line('Sale #' . $sale->id . ' journal_id=' . $sale->journal_id . ' fiscal=' . ((bool) optional($sale->journal)->is_fiscal ? 'true' : 'false') . ' tipoDoc=' . (optional($sale->journal)->document_type_code ?? ''));
            // Mostrar token por empresa de la venta
            $company = \App\Models\Company::query()->with('sunatConnection')->find($sale->company_id);
            $conn2 = optional($company)->sunatConnection;
            $saleCompanyToken = optional($conn2)->token_ikoodev ?? optional($conn2)->token_apiperu ?? '';
            $this->info('Token por empresa de la venta: ' . ($saleCompanyToken !== '' ? $saleCompanyToken : '[empty]'));
        }
    }
})->purpose('Mostrar URL/tokens configurados y token efectivo para Greenter');
