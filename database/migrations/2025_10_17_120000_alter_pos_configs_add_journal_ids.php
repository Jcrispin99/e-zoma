<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

use App\Models\PosConfig;
use App\Models\Journal;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pos_configs', function (Blueprint $table) {
            $table->foreignId('receipt_journal_id')->nullable()->constrained('journals');
            $table->foreignId('invoice_journal_id')->nullable()->constrained('journals');
        });

        // Backfill: map existing sequence selections to their corresponding journals
        try {
            /** @var \Illuminate\Support\Collection<int, PosConfig> $configs */
            $configs = PosConfig::query()->get();
            foreach ($configs as $cfg) {
                $receiptJournal = null;
                $invoiceJournal = null;
                if ($cfg->receipt_sequence_id) {
                    $receiptJournal = Journal::query()->where('sequence_id', $cfg->receipt_sequence_id)->first();
                }
                if ($cfg->invoice_sequence_id) {
                    $invoiceJournal = Journal::query()->where('sequence_id', $cfg->invoice_sequence_id)->first();
                }

                $cfg->receipt_journal_id = $receiptJournal?->id;
                $cfg->invoice_journal_id = $invoiceJournal?->id;
                $cfg->save();
            }
        } catch (\Throwable $e) {
            // If backfill fails, keep migration going; data can be set via UI
            // Log is not available here; swallow to avoid breaking deploy
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_configs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('receipt_journal_id');
            $table->dropConstrainedForeignId('invoice_journal_id');
        });
    }
};