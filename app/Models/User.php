<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

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
}
