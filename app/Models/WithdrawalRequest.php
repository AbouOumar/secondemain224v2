<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Demande de retrait du portefeuille vers un compte Mobile Money. Le montant est débité
 * à la demande ; l'équipe verse l'argent puis marque la demande payée, ou la refuse
 * (le montant retourne alors dans le portefeuille).
 */
class WithdrawalRequest extends Model
{
    public const PENDING = 'en_attente';
    public const PAID = 'paye';
    public const REJECTED = 'refuse';

    public const MIN_AMOUNT = 10000;

    protected $fillable = [
        'reference',
        'user_id',
        'montant',
        'phone',
        'operator',
        'status',
        'note',
        'processed_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
