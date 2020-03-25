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
     * @return array|ClientStorePropertyInterface[][]
     */
    public static function getStore(int $store_id): array
    {
        /**
         * Register Stores Here
         */
        return [
            'user' => self::userStore($store_id),
            'forms' => self::formsStore($store_id)
        ];
    }

    public static function userStore(int $user_id)
    {
        return [
            'users' => new ClientStorePropertyCollection(User::query()),
            'account' => new ClientStorePropertySingle(User::query()),
            'stats' => new ClientStorePropertyRaw(function () {
                return User::query();
            }, null)
        ];
    }

    public static function formsStore(int $account_id)
    {
        return [
            'users' => new ClientStorePropertyCollection(User::query()),
            'account' => new ClientStorePropertySingle(User::query()),
            'stats' => new ClientStorePropertyRaw(function () {
                return User::query();
            }, null),
        ];
    }
}