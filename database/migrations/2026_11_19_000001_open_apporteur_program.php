<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Parrainage ouvert : plus de candidature à valider. Les candidatures en attente ou refusées
 * deviennent des apporteurs actifs avec un code (les suspensions sont conservées).
 */
return new class extends Migration
{
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function up(): void
    {
        $rows = DB::table('apporteurs')->whereIn('status', ['en_attente', 'refuse'])->get(['id', 'code']);

        foreach ($rows as $row) {
            DB::table('apporteurs')->where('id', $row->id)->update([
                'status' => 'actif',
                'code' => $row->code ?? $this->uniqueCode(),
                'note' => null,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Rien à défaire : les codes attribués restent valables.
    }

    private function uniqueCode(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while (DB::table('apporteurs')->where('code', $code)->exists());

        return $code;
    }
};
