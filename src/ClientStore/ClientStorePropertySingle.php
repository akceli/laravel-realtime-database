<?php

namespace Akceli\RealtimeClientStoreSync\ClientStore;

use Akceli\RealtimeClientStoreSync\PusherService\PusherServiceEvent;
use http\Exception\InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Class ClientStorePropertySingle
 * @package Akceli\RealtimeClientStoreSync\ClientStore
 * ClientStorePropertyInterface
 */
class ClientStorePropertySingle implements ClientStorePropertyInterface
{
    use ClientStorePropertyTrait;
    
    /** @var string */
    private $store;
    
    /** @var string */
    private $property;

    /** @var int  */
    private $channel_id;
    
    /** @var string  */
    private $resource;

    private $model;
    private $created_method;
    private $updated_method;
    private $deleted_method;
    private $sendable = true;

    private $builder;

    /**
     * PusherStoreCollection constructor.
     * @param string $store
     * @param string $resource
     * @param $builder
     */
    public function __construct(int $channel_id, string $store, string $property, $builder, string $resource = null)
    {
        $this->channel_id = $channel_id;
        $this->store = $store;
        $this->property = $property;
        $this->resource = $resource ?? ClientStoreDefaultResource::class;
        $this->builder = $builder;
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
        $eagerLoads = array_keys($storeProperty->getBuilder()->getEagerLoads());
        return (new $this->resource($model->load($eagerLoads)))->resolve();
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