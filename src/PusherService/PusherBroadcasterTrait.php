<?php

namespace Akceli\RealtimeClientStoreSync\PusherService;

use Akceli\RealtimeClientStoreSync\ClientStore\ClientStorePropertyCollection;
use App\ClientStore\ClientStore;

/**
 * Trait PusherBroadcasterTrait
 * @package App\Services\PusherServices
 */
trait PusherBroadcasterTrait
{
    /**
     * Get Models to be processed.
     * @return void
     */
    public function broadcastCreatedEvents()
    {
        foreach ($this->store_locations ?? [] as $location) {
            $parts = explode('.', $location);
            $store = $parts[0];
            $prop = $parts[1];
            $store_id = $this->getStoreId($store);
            $store = ClientStore::getStore($store, $store_id)[$prop];

            if ($store instanceof ClientStorePropertyCollection) {
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
    public function broadcastUpdatedEvents()
    {
        foreach ($this->store_locations ?? [] as $location) {
            $parts = explode('.', $location);
            $store = $parts[0];
            $prop = $parts[1];
            $store_id = $this->getStoreId($store);
            $store = ClientStore::getStore($store, $store_id)[$prop];

            if ($store instanceof ClientStorePropertyCollection) {
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
    public function broadcastDeletedEvents()
    {
        foreach ($this->store_locations ?? [] as $location) {
            $parts = explode('.', $location);
            $store = $parts[0];
            $prop = $parts[1];
            $store_id = $this->getStoreId($store);
            $store = ClientStore::getStore($store, $store_id)[$prop];

            if ($store instanceof ClientStorePropertyCollection) {
                PusherService::RemoveFromCollection($location, $store_id, $this->id);
            } else {
                PusherService::SetRoot($location, $store_id, $this);
            }
        }
    }

    /**
     * @param string $store
     * @return int
     */
    public function getStoreId(string $store)
    {
        return ClientStore::getStoreId($store, $this);
    }
}
