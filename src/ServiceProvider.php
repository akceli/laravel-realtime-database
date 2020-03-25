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
            __DIR__ . '/publishable/ClientStore' => base_path('app/ClientStore'),
            __DIR__ . '/publishable/config' => base_path('config'),
	    ]);
    }
}
