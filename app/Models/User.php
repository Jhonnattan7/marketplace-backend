<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // Como vendedor
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    // Como comprador
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class, 'buyer_id');
    }

    public function orderReturns(): HasMany
    {
        return $this->hasMany(OrderReturn::class, 'buyer_id');
    }
}
