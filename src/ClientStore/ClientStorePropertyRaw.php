<?php

namespace Akceli\RealtimeClientStoreSync\ClientStore;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ClientStorePropertyRaw implements ClientStorePropertyInterface
{
    private $data;
    private $default;

    /**
     * PusherStoreSingle constructor.
     * @param $data
     * @param $default
     */
    public function __construct($data, $default = null)
    {
        $this->data = $data;
        $this->default = $default;
        $this->resource = ClientStoreDefaultResource::class;
    }

    public function getDataFromModel(Model $model)
    {
        $data = $this->data;
        return new $this->resource($data());
    }

    public function getData(Request $request)
    {
        $data = $this->data;
        return new $this->resource($data());
    }

    public function getDefaultData()
    {
        return $this->default;
    }

    public function getSingleData(int $id)
    {
        $data = $this->data;
        return new $this->resource($data());
    }
}