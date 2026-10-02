<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IdentityVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'document_type',
        'document_path',
        'selfie_path',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'submitted_at',
    ];

    /** Disque privé (storage/app/private) où sont rangés les documents. */
    public const DISK = 'local';

    /**
     * Réponse HTTP affichant le document ou le selfie, ou null s'il est introuvable.
     */
    public function fileResponse(string $type)
    {
        $path = $type === 'selfie' ? $this->selfie_path : $this->document_path;

        if (! $path || ! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        return Storage::disk(self::DISK)->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function documentIsPdf(): bool
    {
        return str_ends_with(strtolower((string) $this->document_path), '.pdf');
    }

    protected function casts(): array
    {
        return [
            'status' => VerificationStatus::class,
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
