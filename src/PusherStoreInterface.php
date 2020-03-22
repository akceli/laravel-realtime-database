<?php

namespace Akceli\RealtimeClientStoreSync;

use Illuminate\Database\Eloquent\Model;

interface PusherStoreInterface
{
    public function getData();
    public function getDefaultData();
    public function getSingleData(int $id);
    public function getDataFromModel(Model $model);
}