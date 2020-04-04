<?php

namespace Akceli\RealtimeClientStoreSync\ClientStore;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Support\Str;

interface ClientStorePropertyInterface
{
    /**
     * @return string
     */
    public function getStore(): string;

    /**
     * @return string
     */
    public function getProperty(): string;

    /**
     * @param int $pusherEvent
     * @return bool|string
     */
    public function getEventBehavior(int $pusherEvent);

    /**
     * @param Request|null $request
     * @return Resource|array
     */
    public function getData(Request $request = null);

    /**
     * @param Model $model
     * @return array
     */
    public function getDataFromModel(Model $model);

    /**
     * @param int $id
     * @return Resource|array
     */
    public function getSingleData(int $id);
}