<?php

namespace App\ClientStore;

use Akceli\RealtimeClientStoreSync\ClientStore\ClientStoreInterface;
use Akceli\RealtimeClientStoreSync\ClientStore\PusherStoreCollection;
use Akceli\RealtimeClientStoreSync\ClientStore\PusherStoreInterface;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ClientStore implements ClientStoreInterface
{
    /**
     * @param $store
     * @param int $store_id
     * @return array|PusherStoreInterface[]
     */
    public static function getStore($store, int $store_id): array
    {
        $stores = [
            'users' => self::getUserStore($store_id),
        ];

        if (in_array($store, array_keys($stores))) {
            return $stores[$store];
        } else {
            throw new \InvalidArgumentException('Invalid Store Selection. available stores are [' . implode(', ', array_keys($stores)) . ']');
        }
    }

    /**
     * This is used by the SyncClientStoreTrait
     * this will allow you to write a global store_id resolver that can be overwritten at the model level if you chose
     *
     * This is only used when using the $store_locations to resolve the correct channel
     *
     * @param string $store
     * @param Model $model
     * @return int
     */
    public static function getStoreId(string $store, Model $model): int
    {
        return Auth::user()->id;
    }

    /**
     * @param int $account_id
     * @return array|PusherStoreInterface[]
     */
    public static function getUserStore(int $account_id): array
    {
        return [
            'users' => new PusherStoreCollection(User::query()),
        ];
    }
}