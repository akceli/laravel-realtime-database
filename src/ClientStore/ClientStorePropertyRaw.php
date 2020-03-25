<?php

namespace Akceli\RealtimeClientStoreSync\ClientStore;

use Illuminate\Database\Eloquent\Model;

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

    public function getData()
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
        return $this->getData();
    }
}