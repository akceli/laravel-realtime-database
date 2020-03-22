<?php

namespace Akceli\RealtimeClientStoreSync;

use App\Http\Controllers\Api\ClientStoreController;

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
            $store = ClientStoreController::getStore($store, $store_id)[$prop];

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
            $store = ClientStoreController::getStore($store, $store_id)[$prop];

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
            $store = ClientStoreController::getStore($store, $store_id)[$prop];

            if ($store instanceof PusherStoreCollection) {
                PusherService::RemoveFromCollection($location, $store_id, $this->id);
            } else {
                PusherService::SetRoot($location, $store_id, $this);
            }
        }
    }

    public function getStoreId(string $store): int
    {
        if ($store === 'account') return $this->account_id;
        if ($store === 'market') return $this->market_id;
        if ($store === 'activeRecord') return $this->record_id;
        if ($store === 'fus') return $this->account_id;

        return $this->account_id;
    }
}
