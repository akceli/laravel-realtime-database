<?php

namespace Akceli\RealtimeClientStoreSync;

use Illuminate\Database\Eloquent\Model;

class PusherStoreSingle implements PusherStoreInterface
{
    private string $resource;
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
        $model = $this->builder->findOrFail($id);
        return ($this->resource) ? new $this->resource($model) : $model;
    }

    public function getData()
    {
        $model = $this->builder->firstOrFail();
        return ($this->resource) ? new $this->resource($model) : $model;
    }

    public function getDefaultData()
    {
        return null;
    }
}