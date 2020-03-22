<?php

namespace Akceli\RealtimeClientStoreSync\ClientStore;

use Illuminate\Database\Eloquent\Model;

interface ClientStoreInterface
{
    /**
     * @param $store
     * @param int $store_id
     * @return array|PusherStoreInterface[]
     */
    public static function getStore($store, int $store_id): array;

    /**
     * This is used by the SyncClientStoreTrait
     * this will allow you to write a global store_id resolver that can be overwritten at the model level if you chose
     *
     * @param string $store
     * @param Model $model
     * @return int
     */
    public static function getStoreId(string $store, Model $model): int;
}
