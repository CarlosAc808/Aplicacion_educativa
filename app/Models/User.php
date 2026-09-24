<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'xp',
        'completed_missions',
        'streak',
        'is_admin',
        'lives',
        'has_paid_lives',
        'lives_reset_at',
        'premium_until',
        'last_stripe_session_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'xp' => 'integer',
            'completed_missions' => 'integer',
            'streak' => 'integer',
            'is_admin' => 'boolean',
            'lives' => 'integer',
            'has_paid_lives' => 'boolean',
            'lives_reset_at' => 'datetime',
            'premium_until' => 'datetime',
        ];
    }
}
