<?php

namespace Akceli\RealtimeClientStoreSync\ClientStore;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

interface ClientStorePropertyInterface
{
    public function getDefaultData();
    public function getData(Request $request);
    public function getDataFromModel(Model $model);
    public function getSingleData(int $id);
}