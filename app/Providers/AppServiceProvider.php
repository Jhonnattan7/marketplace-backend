<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('is-vendedor', fn(User $user) => $user->hasRole(['vendedor', 'seller']));
        Gate::define('is-comprador', fn(User $user) => $user->hasRole(['comprador', 'buyer']));
        Gate::define('is-seller', fn(User $user) => $user->hasRole(['seller', 'vendedor']));
        Gate::define('is-buyer', fn(User $user) => $user->hasRole(['buyer', 'comprador']));

        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
    }
}
