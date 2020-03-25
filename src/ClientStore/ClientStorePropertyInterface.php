<?php

namespace Akceli\RealtimeClientStoreSync\ClientStore;

use Illuminate\Database\Eloquent\Model;

interface ClientStorePropertyInterface
{
    public function getData();
    public function getDefaultData();
    public function getSingleData(int $id);
    public function getDataFromModel(Model $model);
}