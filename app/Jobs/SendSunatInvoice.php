<?php

namespace App\Jobs;

use App\Models\Sale;
use App\Services\GreenterInvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSunatInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // afterCommit se configurará al despachar el Job usando el método fluido ->afterCommit()

    public int $saleId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $saleId)
    {
        $this->saleId = $saleId;
    }

    /**
     * Execute the job.
     */
    public function handle(GreenterInvoiceService $service): void
    {
        try {
            // Cargar venta (para futuras validaciones o mapeo dinámico)
            $sale = Sale::with('journal')->find($this->saleId);

            if (!$sale) {
                Log::warning('SendSunatInvoice: sale not found', ['sale_id' => $this->saleId]);
                return;
            }

            // Enviar usando datos de la venta (cliente y journal dinámicos)
            $service->sendInvoiceFromSale($sale);
        } catch (\Throwable $e) {
            Log::error('SendSunatInvoice failed', [
                'sale_id' => $this->saleId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}