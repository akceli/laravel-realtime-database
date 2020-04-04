<?php

namespace Akceli\RealtimeClientStoreSync\ClientStore;

use Akceli\RealtimeClientStoreSync\PusherService\PusherServiceEvent;
use http\Exception\InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClientStorePropertySingle implements ClientStorePropertyInterface
{
    /** @var string */
    private $store;
    
    /** @var string */
    private $property;
    
    /** @var string  */
    private $resource;

    private $created;
    private $updated;
    private $deleted;
    
    private $builder;

    const AcceptableEventValues = [ClientStoreService::CreatedEvent, ClientStoreService::UpdatedEvent, ClientStoreService::DeletedEvent, true, false];

    /**
     * PusherStoreCollection constructor.
     * @param string $store
     * @param string $resource
     * @param $builder
     */
    public function __construct(string $store, string $property, $builder, string $resource = null, $created = true, $updated = true, $deleted = true)
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
        $this->resource = $resource ?? ClientStoreDefaultResource::class;
        $this->builder = $builder;
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
        return (new $this->resource($model))->resolve();
    }

    public function getSingleData(int $id)
    {
        $model = $this->getBuilder()->find($id);
        return new $this->resource($model);
    }

    public function getData(Request $request = null)
    {
        $model = $this->getBuilder()->firstOrFail();
        return new $this->resource($model);
    }

    public function getDefaultData()
    {
        return null;
    }

    /**
     * @return Builder
     */
    public function getBuilder()
    {
        if (is_callable($this->builder)) {
            $builder = $this->builder;
            return $builder();
        }

        return $this->builder;
    }
}