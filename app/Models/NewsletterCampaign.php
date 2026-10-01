<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterCampaign extends Model
{
    public const DRAFT = 'draft';
    public const SENDING = 'sending';
    public const SENT = 'sent';

    protected $fillable = [
        'subject',
        'content',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveries()
    {
        return $this->hasMany(NewsletterDelivery::class);
    }

    /**
     * Abonnés qui n'ont pas encore reçu cette campagne.
     */
    public function pendingRecipients()
    {
        return User::newsletterRecipients()
            ->whereNotIn('id', NewsletterDelivery::select('user_id')->where('newsletter_campaign_id', $this->id));
    }

    public function isDraft(): bool
    {
        return $this->status === self::DRAFT;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::SENDING => 'En cours d\'envoi',
            self::SENT => 'Envoyée',
            default => 'Brouillon',
        };
    }
}
