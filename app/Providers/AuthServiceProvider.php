<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // kita membuat gate dengan nama order-view, 
        // dimana dia meminta dua parameter yakni customer dan order
        Gate::define('order-view', function ($customer, $order) {
            // kemudian di check, jika customer id sama dengan customer_id yang ada pada table order
            // maka return-nya adalah TRUE
            // gate ini hanya akan me-return true/false sebagai tanda diizinkan atau tidak
            return $customer->id == $order->customer_id;
        });
    }
}
