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
            __DIR__ . '/publishable/client_store.php' => base_path('app/ClientStore/ClientStore.php'),
	    ]);
    }
}
