<?php

namespace Akceli\RealtimeClientStoreSync\PusherService;

class PusherServiceMethod
{
    const SetRoot = 'setRoot';
    const PatchRoot = 'updateRoot';
    const UpdateInCollection = 'updateInCollection';
    const AddToCollection = 'addToCollection';
    const RemoveFromCollection = 'removeFromCollection';
    const UpsertCollection = 'upsertCollection';

    public static function UpsertCollection(bool $add_or_update)
    {
        return ($add_or_update) ? self::UpsertCollection : self::RemoveFromCollection;
    }
}
