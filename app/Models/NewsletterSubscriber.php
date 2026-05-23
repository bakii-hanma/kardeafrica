<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'token',
        'source',
        'locale',
        'is_active',
        'ip_address',
        'user_agent',
        'subscribed_at',
        'unsubscribed_at',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'subscribed_at'   => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $sub) {
            if (empty($sub->token)) {
                $sub->token = self::generateToken();
            }
            if (empty($sub->subscribed_at)) {
                $sub->subscribed_at = now();
            }
        });
    }

    public static function generateToken(): string
    {
        do {
            $token = Str::random(48);
        } while (self::where('token', $token)->exists());
        return $token;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function unsubscribe(): void
    {
        $this->update([
            'is_active'       => false,
            'unsubscribed_at' => now(),
        ]);
    }

    public function resubscribe(): void
    {
        $this->update([
            'is_active'       => true,
            'unsubscribed_at' => null,
            'subscribed_at'   => now(),
        ]);
    }
}
