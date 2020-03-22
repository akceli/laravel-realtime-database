<?php

namespace Akceli\RealtimeClientStoreSync\PusherService;

interface PusherBroadcasterInterface
{
    /**
     * Get Models to be processed.
     * @return void
     */
    public function broadcastCreatedEvents();

    /**
     * Get Models to be processed.
     * @return void
     */
    public function broadcastUpdatedEvents();

    /**
     * Get Models to be processed.
     * @return void
     */
    public function broadcastDeletedEvents();

    /**
     * @param string $store
     * @return int
     */
    public function getStoreId(string $store): int;
}
