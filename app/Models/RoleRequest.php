<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Demande pour devenir livreur (motard) ou revendeur pro. Ces rôles manipulent de l'argent et des
 * colis d'autrui : ils ne s'obtiennent plus à l'inscription mais après examen par l'administration.
 */
class RoleRequest extends Model
{
    public const PENDING = 'en_attente';
    public const APPROVED = 'approuve';
    public const REJECTED = 'refuse';

    /** Rôles qui s'obtiennent sur demande (acheteur et vendeur sont ouverts à tous). */
    public const REQUESTABLE = ['motard', 'revendeur_pro'];

    protected $fillable = ['user_id', 'role', 'status', 'details', 'note', 'processed_by', 'processed_at'];

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
}
