<?php

namespace Akceli\RealtimeClientStoreSync\ClientStore;

use Akceli\RealtimeClientStoreSync\PusherService\PusherServiceEvent;
use http\Exception\InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClientStorePropertyCollection implements ClientStorePropertyInterface
{
    /** @var string */
    private $store;

    /** @var string */
    private $property;
    
    private $created;
    private $updated;
    private $deleted;

    /** @var string */
    private $resource;
    private $builder;
    private $size;

    const AcceptableEventValues = [ClientStoreService::CreatedEvent, ClientStoreService::UpdatedEvent, ClientStoreService::DeletedEvent, true, false];

    /**
     * PusherStoreCollection constructor.
     * @param string $store
     * @param string $resource
     * @param $builder
     * @param int $size
     */
    public function __construct(string $store, string $property, $builder, string $resource = null, int $size = null, $created = true, $updated = true, $deleted = true)
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
        $this->size = $size ?? config('client-store.default_pagination_size');
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
        return new $this->resource($this->getBuilder()->find($id));
    }

    public function getData(Request $request = null)
    {
        $size = $request->get('size', $this->size);
        $page = $request->get('page');
        $total = $request->get('total');
        $after = $request->get('after');
        $after_column = $request->get('after_column', 'id');
        if ($after) {
            $collection = $this->getBuilder()->forPageAfterId($size, $after, $after_column)->paginate($size);
        } elseif ($total) {
            $collection = $this->getBuilder()->skip($total)->take($size)->get();
        } else {
            $collection = $this->getBuilder()->paginate($size, '*', 'page', $page);
        }

        return $this->resource::collection($collection);
    }

    public function getDefaultData()
    {
        return [];
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