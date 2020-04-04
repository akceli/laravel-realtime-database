<?php

namespace Akceli\RealtimeClientStoreSync\PusherService;

class ClientStoreActions
{
    const SetRoot = 'setRoot';
    const PatchRoot = 'updateRoot';
    const UpdateInCollection = 'updateInCollection';
    const AddToCollection = 'addToCollection';
    const RemoveFromCollection = 'removeFromCollection';
    const UpsertCollection = 'upsertCollection';
    const DoNothing = false;
    
    public static function UpsertOrRemoveFromCollection(bool $add_or_update)
    {
        return ($add_or_update) ? self::UpsertCollection : self::RemoveFromCollection;
    }
}
