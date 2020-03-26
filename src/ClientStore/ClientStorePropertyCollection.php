<?php

namespace Akceli\RealtimeClientStoreSync\ClientStore;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ClientStorePropertyCollection implements ClientStorePropertyInterface
{
    /** @var string */
    private $resource;
    private $builder;

    /**
     * PusherStoreCollection constructor.
     * @param string $resource
     * @param $builder
     */
    public function __construct($builder, string $resource = null)
    {
        $this->resource = $resource;
        $this->builder = $builder;
    }

    public function getDataFromModel(Model $model)
    {
        return ($this->resource) ? (new $this->resource($model))->resolve() : $model->toArray();
    }

    public function getSingleData(int $id)
    {
        $model = $this->getBuilder()->findOrFail($id);
        return ($this->resource) ? new $this->resource($model) : $model;
    }

    public function getData(Request $request)
    {
        $collection = $this->getBuilder()->get();
        return ($this->resource) ? $this->resource::collection($collection) : $collection;
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