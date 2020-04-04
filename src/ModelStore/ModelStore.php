<?php

namespace Akceli\RealtimeClientStoreSync\ModelStore;

class ModelStore
{
    private string $store;
    private string $property;
    private string $channel_id;
    
    /**
     * ModelStore constructor.
     * 
     * @param string $store
     * @param string $property
     * @param int $channel_id
     */
    public function __construct(string $store, string $property, int $channel_id)
    {
        $this->store = $store;
        $this->property = $property;
        $this->channel_id = $channel_id;
    }

    public function __toString()
    {
        return $this->store . '.' . $this->property . ':' . $this->channel_id;
    }

}