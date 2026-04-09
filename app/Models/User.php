<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use \Laravel\Sanctum\HasApiTokens;
    use HasFactory;
    use Notifiable;
    use \Spatie\Permission\Traits\HasRoles;

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



    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class, 'buyer_id');
    }

    public function sellerProfile(): HasOne
    {
        return $this->hasOne(SellerProfile::class);
    }



    public function buyerProfile(): HasOne
    {
        return $this->hasOne(BuyerProfile::class);
    }
}
