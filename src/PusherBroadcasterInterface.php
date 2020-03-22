<?php

namespace Akceli\RealtimeClientStoreSync;

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
}
