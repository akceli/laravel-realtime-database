<?php

namespace App\ClientStore;

use Akceli\RealtimeClientStoreSync\ClientStore\PusherStoreCollection;
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
        } else {
            PusherService::created($this);
        }

        return $result;
    }

    public function delete()
    {
        PusherService::deleted($this);
        return parent::delete();
    }

    /**
     * Get Models to be processed.
     * @return void
     */
    public function broadcastCreatedEvents(): void
    {
        foreach ($this->store_locations ?? [] as $location) {
            $store = explode('.', $location)[0];
            $prop = explode('.', $location)[1];
            $store_id = $this->getStoreId($store);
            $store = ClientStore::getStore($store, $store_id)[$prop];

            if ($store instanceof PusherStoreCollection) {
                PusherService::AddToCollection($location, $store_id, $this);
            } else {
                PusherService::SetRoot($location, $store_id, $this);
            }
        }
    }

    /**
     * Get Models to be processed.
     * @return void
     */
    public function broadcastUpdatedEvents(): void
    {
        foreach ($this->store_locations ?? [] as $location) {
            $store = explode('.', $location)[0];
            $prop = explode('.', $location)[1];
            $store_id = $this->getStoreId($store);
            $store = ClientStore::getStore($store, $store_id)[$prop];

            if ($store instanceof PusherStoreCollection) {
                PusherService::UpdateInCollection($location, $store_id, $this);
            } else {
                PusherService::SetRoot($location, $store_id, $this);
            }
        }
    }

    /**
     * Get Models to be processed.
     * @return void
     */
    public function broadcastDeletedEvents(): void
    {
        foreach ($this->store_locations ?? [] as $location) {
            $store = explode('.', $location)[0];
            $prop = explode('.', $location)[1];
            $store_id = $this->getStoreId($store);
            $store = ClientStore::getStore($store, $store_id)[$prop];

            if ($store instanceof PusherStoreCollection) {
                PusherService::RemoveFromCollection($location, $store_id, $this->id);
            } else {
                PusherService::SetRoot($location, $store_id, $this);
            }
        }
    }

    public function getStoreId(string $store): int
    {
        return ClientStore::getStoreId($store, $this);
    }
}