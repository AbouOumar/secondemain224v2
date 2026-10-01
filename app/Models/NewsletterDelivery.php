<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterDelivery extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'newsletter_campaign_id',
        'user_id',
        'failed',
    ];

    protected function casts(): array
    {
        return ['failed' => 'boolean'];
    }
}
