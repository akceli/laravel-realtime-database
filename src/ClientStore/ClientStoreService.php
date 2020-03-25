<?php

namespace Akceli\RealtimeClientStoreSync\ClientStore;

class ClientStoreService
{
    /**
     * @param $store
     * @param int $store_id
     * @return array|ClientStorePropertyInterface[]
     */
    public static function getStore($store, int $store_id): array
    {
        $stores = config('client-store.store')::getStore($store_id);

        if (in_array($store, array_keys($stores))) {
            return $stores[$store];
        } else {
            throw new \InvalidArgumentException('Invalid Store Selection. available stores are [' . implode(', ', array_keys($stores)) . ']');
        }
    }
}