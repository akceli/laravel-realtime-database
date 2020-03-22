<?php

namespace Akceli\RealtimeClientStoreSync;

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
    public function broadcastCreatedEvents(): void
    {
        foreach ($this->store_locations ?? [] as $location) {
            [$store, $prop] = explode('.', $location);
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
            [$store, $prop] = explode('.', $location);
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
            [$store, $prop] = explode('.', $location);
            $store_id = $this->getStoreId($store);
            $store = ClientStore::getStore($store, $store_id)[$prop];

            if ($store instanceof PusherStoreCollection) {
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
    public function getStoreId(string $store): int
    {
        return ClientStore::getStoreId($store, $this);
    }
}
