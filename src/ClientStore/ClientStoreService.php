<?php

namespace Akceli\RealtimeClientStoreSync\ClientStore;

use Akceli\RealtimeClientStoreSync\PusherService\PusherService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

class ClientStoreService
{
    const CreatedEvent = 'created';
    const UpdatedEvent = 'updated';
    const DeletedEvent = 'deleted';
    
    /**
     * @param $store
     * @param int $store_id
     * @return array|ClientStorePropertyInterface[]
     */
    public static function getStore($store, int $store_id): array
    {
        $stores = self::getStores($store_id);

        if (in_array($store, array_keys($stores))) {
            return $stores[$store];
        } else {
            throw new \InvalidArgumentException('Invalid Store Selection. available stores are [' . implode(', ', array_keys($stores)) . ']');
        }
    }

    /**
     * @param int $store_id
     * @return array|ClientStorePropertyInterface[]
     */
    private static function getStores(int $store_id): array
    {
        $clientStoreDir = config('client-store.client_store_dir');
        $stores = [];
        $files = collect(scandir(app_path($clientStoreDir)))
            ->filter(fn(string $file_name) => Str::endsWith($file_name, 'Store.php'))
            ->toArray();

        foreach ($files as $file) {
            $key = Str::camel(substr($file, 0, -9));
            $class = 'App\\' . $clientStoreDir . '\\' . substr($file, 0, -4);
            $method = 'getProperties';
            $store = $class::$method($store_id);
            $stores[$key] = $store;
        }

        return $stores;
    }

    public static function boot()
    {
        Event::listen('eloquent.created: *', function($event, $data) {
            $model_name = explode(': ', $event);
            $model_id = $data[0]->id;
        });
        Event::listen('eloquent.updated: *', function($event, $data) {
            $model_name = explode(': ', $event);
            $model_id = $data[0]->id;
        });
        Event::listen('eloquent.deleted: *', function($event, $data) {
            $model_name = explode(': ', $event);
            $model_id = $data[0]->id;
        });
    }
    
    public static function ignoreChanges(callable $callback)
    {
        PusherService::disableTracking();
        
        $callback();
        
        PusherService::enableTracking();
    }
}