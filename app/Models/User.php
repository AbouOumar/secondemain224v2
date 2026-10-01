<?php

namespace App\Models;

use App\Enums\EmailCategory;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Notifications\Auth\ResetPasswordNotification;
use App\Notifications\Auth\VerifyEmailNotification;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, CanResetPassword;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'avatar',
        'latitude',
        'longitude',
        'is_verified',
        'verified_at',
        'verification_documents',
        'rider_status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_preferences' => 'array',
            'newsletter_subscribed_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_online_at' => 'datetime',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'verification_documents' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Une nouvelle adresse e-mail doit être reconfirmée.
        static::updating(function (User $user) {
            if ($user->isDirty('email') && ! $user->isDirty('email_verified_at')) {
                $user->email_verified_at = null;
            }
        });

        static::updated(function (User $user) {
            if ($user->wasChanged('email') && $user->email && ! $user->hasVerifiedEmail()) {
                $user->sendEmailVerificationNotification();
            }
        });
    }

    /**
     * L'utilisateur reçoit-il les e-mails de cette catégorie ?
     * Uniquement vers une adresse confirmée, pour ne pas écrire à un inconnu.
     */
    public function wantsEmailFor(EmailCategory $category): bool
    {
        return $this->email
            && $this->hasVerifiedEmail()
            && $this->emailPreferenceEnabled($category);
    }

    /**
     * Choix de l'utilisateur pour cette catégorie, indépendamment de l'adresse.
     */
    public function emailPreferenceEnabled(EmailCategory $category): bool
    {
        if ($category === EmailCategory::Newsletter) {
            return $this->newsletter_subscribed_at !== null;
        }

        return ! in_array($category->value, $this->email_preferences['disabled'] ?? [], true);
    }

    public function setEmailPreference(EmailCategory $category, bool $enabled): void
    {
        if ($category === EmailCategory::Newsletter) {
            if ($enabled !== $this->emailPreferenceEnabled($category)) {
                $this->forceFill(['newsletter_subscribed_at' => $enabled ? now() : null])->save();
            }

            return;
        }

        $disabled = collect($this->email_preferences['disabled'] ?? [])
            ->reject(fn ($value) => $value === $category->value);

        if (! $enabled) {
            $disabled->push($category->value);
        }

        $this->forceFill(['email_preferences' => ['disabled' => $disabled->values()->all()]])->save();
    }

    public function scopeNewsletterRecipients($query)
    {
        return $query->whereNotNull('newsletter_subscribed_at')
            ->whereNotNull('email')
            ->whereNotNull('email_verified_at')
            ->where('status', '!=', 'suspendu');
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function ordersAsBuyer()
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function ordersAsSeller()
    {
        return $this->hasMany(Order::class, 'seller_id');
    }

    public function deliveriesAsRider()
    {
        return $this->hasMany(Delivery::class, 'rider_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class, 'rated_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function partner()
    {
        return $this->hasOne(Partner::class);
    }

    public function oauthProviders()
    {
        return $this->hasMany(OauthProvider::class);
    }
 
    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }
 
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function savedArticles()
    {
        return $this->belongsToMany(Article::class, 'article_user_favorites');
    }

    public function identityVerifications()
    {
        return $this->hasMany(IdentityVerification::class);
    }

    public function latestIdentityVerification()
    {
        return $this->hasOne(IdentityVerification::class)->latestOfMany();
    }

    public function reportsMade()
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function offersMade()
    {
        return $this->hasMany(Offer::class, 'buyer_id');
    }

    public function offersReceived()
    {
        return $this->hasMany(Offer::class, 'seller_id');
    }

    public function searchAlerts()
    {
        return $this->hasMany(SearchAlert::class);
    }
}
