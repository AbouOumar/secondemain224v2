<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Apporteur d'affaires : recrute des vendeurs avec son code personnel et touche une part
 * de la commission de la plateforme sur la première vente de chacun.
 * Indépendant du rôle du compte (un motard ou un vendeur peut être apporteur).
 */
class Apporteur extends Model
{
    public const PENDING = 'en_attente';
    public const ACTIVE = 'actif';
    public const REJECTED = 'refuse';
    public const SUSPENDED = 'suspendu';

    protected $fillable = ['user_id', 'code', 'status', 'details', 'note', 'processed_by', 'processed_at'];

    protected function casts(): array
    {
        return [
            'details' => 'array',
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

    /** Vendeurs inscrits avec le code de cet apporteur. */
    public function referrals()
    {
        return $this->hasMany(User::class, 'apporteur_id');
    }

    public function commissions()
    {
        return $this->hasMany(ApporteurCommission::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::ACTIVE;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::PENDING => 'En attente',
            self::ACTIVE => 'Actif',
            self::REJECTED => 'Refusé',
            self::SUSPENDED => 'Suspendu',
            default => $this->status,
        };
    }
}
