<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'is_active',
        'preferred_currency',
        'preferred_language',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
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
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relation avec le profil utilisateur
     */
    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * Relation avec les commandes
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Relation avec les paiements
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Relation avec le panier
     */
    public function cartItems()
    {
        return $this->hasMany(ShoppingCart::class);
    }

    /**
     * Vérifie si l'utilisateur est admin
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Vérifie si l'utilisateur est modérateur
     */
    public function isModerator()
    {
        return $this->role === 'moderator';
    }

    /**
     * Récupère les utilisateurs actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
