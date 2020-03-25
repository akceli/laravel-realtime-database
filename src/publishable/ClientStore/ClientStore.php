<?php

namespace App\ClientStore;

use Akceli\RealtimeClientStoreSync\ClientStore\ClientStoreInterface;
use Akceli\RealtimeClientStoreSync\ClientStore\ClientStorePropertyCollection;
use Akceli\RealtimeClientStoreSync\ClientStore\ClientStorePropertyInterface;
use Akceli\RealtimeClientStoreSync\ClientStore\ClientStorePropertyRaw;
use Akceli\RealtimeClientStoreSync\ClientStore\ClientStorePropertySingle;
use App\User;

class ClientStore implements ClientStoreInterface
{
    /**
     * @param $store
     * @param int $store_id
     * @return array|ClientStorePropertyInterface[]
     */
    public static function getStore($store, int $store_id): array
    {
        /**
         * Register Stores Here
         */
        $stores = [
            'users' => [
                'users' => new ClientStorePropertyCollection(User::query()),
                'account' => new ClientStorePropertySingle(User::query()),
                'stats' => new ClientStorePropertyRaw(function () {
                    return User::query();
                }, null),
            ],
            'forms' => [
                'users' => new ClientStorePropertyCollection(User::query()),
                'account' => new ClientStorePropertySingle(User::query()),
                'stats' => new ClientStorePropertyRaw(function () {
                    return User::query();
                }, null),
            ]
        ];

        if (in_array($store, array_keys($stores))) {
            return $stores[$store];
        } else {
            throw new \InvalidArgumentException('Invalid Store Selection. available stores are [' . implode(', ', array_keys($stores)) . ']');
        }
    }
}