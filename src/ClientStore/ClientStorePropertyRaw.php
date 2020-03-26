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
    public function __construct($data, $default)
    {
        $this->data = $data;
        $this->default = $default;
    }

    public function getDataFromModel(Model $model)
    {
        $data = $this->data;
        return $data();
    }

    public function getData(Request $request)
    {
        $data = $this->data;
        return $data();
    }

    public function getDefaultData()
    {
        return $this->getDefaultData();
    }

    public function getSingleData(int $id)
    {
        $data = $this->data;
        return $data();
    }
}