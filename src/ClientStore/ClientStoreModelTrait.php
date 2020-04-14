<?php

namespace Akceli\RealtimeClientStoreSync\ClientStore;

use Akceli\RealtimeClientStoreSync\PusherService\PusherService;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait BaseModel
 * @package App\ClientStore
 *
 * @mixin Model
 */
trait ClientStoreModelTrait
{
    public function save(array $options = [])
    {
        $exists = $this->exists;
        $result = parent::save($options);

        if ($exists) {
            PusherService::updated($this);
            $result = parent::save($options);
        } else {
            $dirty = $this->getDirty();
            $result = parent::save($options);
            PusherService::created($this, $dirty);
        }

        return $result;
    }

    public function delete()
    {
        PusherService::deleted($this);
        return parent::delete();
    }
}