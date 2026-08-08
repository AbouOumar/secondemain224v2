<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetToken extends Model
{
    protected $table = 'password_reset_phone_tokens';

    protected $primaryKey = 'phone';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'phone',
        'token',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
