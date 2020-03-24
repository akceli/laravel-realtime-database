<?php

namespace App\ClientStore;

use Akceli\RealtimeClientStoreSync\ClientStore\ClientStoreInterface;
use Akceli\RealtimeClientStoreSync\ClientStore\PusherStoreCollection;
use Akceli\RealtimeClientStoreSync\ClientStore\PusherStoreInterface;
use Akceli\RealtimeClientStoreSync\ClientStore\PusherStoreRaw;
use Akceli\RealtimeClientStoreSync\ClientStore\PusherStoreSingle;
use App\User;

class ClientStore implements ClientStoreInterface
{
    /**
     * @param $store
     * @param int $store_id
     * @return array|PusherStoreInterface[]
     */
    public static function getStore($store, int $store_id): array
    {
        /**
         * Register Stores Here
         */
        $stores = [
            'users' => [
                'users' => new PusherStoreCollection(User::query()),
                'account' => new PusherStoreSingle(User::query()),
                'stats' => new PusherStoreRaw(function () {
                    return User::query();
                }, null),
            ],
            'forms' => [
                'users' => new PusherStoreCollection(User::query()),
                'account' => new PusherStoreSingle(User::query()),
                'stats' => new PusherStoreRaw(function () {
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