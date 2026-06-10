<?php

namespace App\Models;

use App\Enums\TransactionType;
use App\Enums\TransactionSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'type',
        'montant',
        'reference',
        'source',
        'source_id',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'source' => TransactionSource::class,
        ];
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
