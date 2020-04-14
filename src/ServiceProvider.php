<?php

namespace Akceli\RealtimeClientStoreSync;

use Illuminate\Support\ServiceProvider as Provider;

class ServiceProvider extends Provider
{
    public function register()
    {
        $this->commands([]);
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/publishable/Resources' => base_path('app/Http/Resources'),
            __DIR__ . '/publishable/ClientStores' => base_path('app/ClientStores'),
            __DIR__ . '/publishable/config' => base_path('config'),
	    ]);
    }
}
