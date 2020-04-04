<?php

namespace Akceli\RealtimeClientStoreSync\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class UpdateClientEvent implements ShouldBroadcast
{
    use SerializesModels;

    public array $data;
    private array $channels;
    private string $event;
    public string $broadcastQueue = 'pusher';

    /**
     * PipelineUpdatedEvent constructor.
     * @param array $data
     * @param string $event
     * @param array $channels
     */
    public function __construct(array $data, string $event, array $channels)
    {
        $this->data = $data;
        $this->channels = $channels;
        $this->event = $event;
    }

    public function broadcastOn()
    {
        return $this->channels;
    }

    public function broadcastAs()
    {
        return $this->event;
    }
}
