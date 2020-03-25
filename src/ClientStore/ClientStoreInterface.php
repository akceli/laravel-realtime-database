<?php

namespace Akceli\RealtimeClientStoreSync\ClientStore;

use Illuminate\Database\Eloquent\Model;

interface ClientStoreInterface
{
    /**
     * @param $store
     * @param int $store_id
     * @return array|ClientStorePropertyInterface[]
     */
    public static function getStore($store, int $store_id): array;
}
