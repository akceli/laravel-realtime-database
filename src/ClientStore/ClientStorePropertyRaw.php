<?php

namespace Akceli\RealtimeClientStoreSync\ClientStore;

use Akceli\RealtimeClientStoreSync\PusherService\PusherServiceEvent;
use http\Exception\InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClientStorePropertyRaw implements ClientStorePropertyInterface
{
    /** @var string */
    private $store;

    /** @var string */
    private $property;

    private $created;
    private $updated;
    private $deleted;
    
    private $data;
    private $default;

    const AcceptableEventValues = [ClientStoreService::CreatedEvent, ClientStoreService::UpdatedEvent, ClientStoreService::DeletedEvent, true, false];

    /**
     * PusherStoreSingle constructor.
     * @param string $store
     * @param $data
     * @param $default
     */
    public function __construct(string $store, string $property, $data, $default = null, $created = true, $updated = true, $deleted = true)
    {
        if (!in_array($created, self::AcceptableEventValues)) {
            throw new InvalidArgumentException('Created can only contain ' . json_encode(self::AcceptableEventValues));
        }
        if (!in_array($updated, self::AcceptableEventValues)) {
            throw new InvalidArgumentException('Updated can only contain ' . json_encode(self::AcceptableEventValues));
        }
        if (!in_array($deleted, self::AcceptableEventValues)) {
            throw new InvalidArgumentException('Deleted can only contain ' . json_encode(self::AcceptableEventValues));
        }

        $this->store = $store;
        $this->property = $property;
        $this->created = $created;
        $this->updated = $updated;
        $this->deleted = $deleted;
        $this->data = $data;
        $this->default = $default;
        $this->resource = ClientStoreDefaultResource::class;
    }

    public function getEventBehavior(int $pusherEvent)
    {
        if ($pusherEvent === PusherServiceEvent::Created) {
            return ($this->created !== true) ?? ClientStoreService::CreatedEvent;
        }
        if ($pusherEvent === PusherServiceEvent::Updated) {
            return ($this->updated !== true) ?? ClientStoreService::UpdatedEvent;
        }
        if ($pusherEvent === PusherServiceEvent::Deleted) {
            return ($this->deleted !== true) ?? ClientStoreService::DeletedEvent;
        }

        throw new InvalidArgumentException('Only valid Pusher Envent Types are ' . json_encode([
            PusherServiceEvent::Created,
            PusherServiceEvent::Updated,
            PusherServiceEvent::Deleted,
        ]));
    }

    public function getStore(): string
    {
        return $this->store;
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function getDataFromModel(Model $model)
    {
        $data = $this->data;
        return $data();
    }

    public function getData(Request $request = null)
    {
        $data = $this->data;
        return $data();
    }

    public function getDefaultData()
    {
        return $this->default;
    }

    public function getSingleData(int $id)
    {
        $data = $this->data;
        return $data();
    }
}