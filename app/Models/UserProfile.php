<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'date_of_birth',
        'gender',
        'country',
        'city',
        'address',
        'avatar',
        'email_verified',
        'phone_verified',
        'last_login',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'email_verified' => 'boolean',
        'phone_verified' => 'boolean',
        'last_login' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
