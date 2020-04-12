<?php

namespace App\ClientStores;

use Akceli\RealtimeClientStoreSync\ClientStore\ClientStoreBase;
use App\Resources\UsersStoreUsersPropertyResource;
use App\User;

class UsersStore extends ClientStoreBase
{
    public static function usersProperty(int $user_id)
    {
        return self::single($user_id,
            User::query()->where('id', '=', $user_id),
            UsersStoreUsersPropertyResource::class
        );
    }
}