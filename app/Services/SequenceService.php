<?php

namespace App\Services;

use App\Models\Journal;
use Illuminate\Support\Facades\DB;

class SequenceService
{
    public static function getNextParts(int $journalId): array
    {
        return DB::transaction(function () use ($journalId) {
            $journal = Journal::with('sequence')->findOrFail($journalId);
            $sequence = $journal->sequence;

            // Bloquear la fila de la secuencia para evitar condiciones de carrera
            $sequence = \App\Models\Sequence::where('id', $sequence->id)->lockForUpdate()->first();

            $nextNumber = $sequence->next_number;
            
            // Formatear el número correlativo con ceros a la izquierda
            $correlative = str_pad($nextNumber, $sequence->sequence_size, '0', STR_PAD_LEFT);

            // Incrementar el próximo número
            $sequence->next_number = $nextNumber + $sequence->step;
            $sequence->save();

            return [
                'serie' => $journal->code,
                'correlative' => $correlative,
            ];
        });
    }
}