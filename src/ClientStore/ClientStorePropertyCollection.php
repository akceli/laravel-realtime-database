<?php

namespace Akceli\RealtimeClientStoreSync\ClientStore;

use Akceli\RealtimeClientStoreSync\PusherService\ClientStoreActions;
use Akceli\RealtimeClientStoreSync\PusherService\PusherServiceEvent;
use http\Exception\InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Class ClientStorePropertyCollection
 * @package Akceli\RealtimeClientStoreSync\ClientStore
 * @mixin ClientStorePropertyInterface
 */
class ClientStorePropertyCollection implements ClientStorePropertyInterface
{
    use ClientStorePropertyTrait;
    
    /** @var string */
    private $store;

    /** @var string */
    private $property;

    /** @var int  */
    private $channel_id;

    private $model;
    private $dirty_attributes;
    private $created_method;
    private $updated_method;
    private $deleted_method;
    private $sendable = true;

    /** @var string */
    private $resource;
    private $builder;
    private $size;

    /**
     * PusherStoreCollection constructor.
     * @param string $store
     * @param string $resource
     * @param $builder
     * @param int $size
     */
    public function __construct(int $channel_id, string $store, string $property, $builder, string $resource = null, int $size = null)
    {
        $this->channel_id = $channel_id;
        $this->store = $store;
        $this->property = $property;
        $this->resource = $resource ?? ClientStoreDefaultResource::class;
        $this->builder = $builder;
        $this->size = $size ?? config('client-store.default_pagination_size');
    }

    public function validateModelIsForStore($model)
    {
        if (get_class($this->getBuilder()->getModel()) !== get_class($model)) {
            throw new \InvalidArgumentException(get_class($model) . ' is not the same model used in the builder ' . get_class($this->getBuilder()->getModel()) . " used in {$this->getStore()}.{$this->getProperty()}");
        }
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
        $eagerLoads = array_keys($this->getBuilder()->getEagerLoads());
        return (new $this->resource($model->load($eagerLoads)))->resolve();
    }
    
    public function getSingleData(int $id)
    {
        return new $this->resource($this->getBuilder()->find($id));
    }

    public function getData(Request $request = null)
    {
        $size = $request->get('size', $this->size);
        $page = $request->get('page');
        $offset = $request->get('offset');
        $after = $request->get('after');
        $after_column = $request->get('after_column', 'id');
        if ($after) {
            $collection = $this->getBuilder()->forPageAfterId($size, $after, $after_column)->paginate($size);
        } elseif ($offset) {
            $collection = $this->getBuilder()->skip($offset)->take($size)->get();
        } else {
            $collection = $this->getBuilder()->paginate($size, '*', 'page', $page);
        }

        return $this->resource::collection($collection);
    }

    public function getDefaultData()
    {
        return $this->resource::collection(collect([]));
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