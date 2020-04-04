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

    /**
     * @param Model $model
     * @param bool $upsert
     * @return ClientStorePropertyInterface
     */
    public function setModel(Model $model = null, bool $upsert = true): ClientStorePropertyInterface;

    /**
     * @return Model|null
     */
    public function getModel();

    /**
     * @param $client_store_action
     * @return ClientStorePropertyInterface
     */
    public function setCreatedAction($client_store_action): ClientStorePropertyInterface;

    /**
     * @param $client_store_action
     * @return ClientStorePropertyInterface
     */
    public function setUpdatedAction($client_store_action): ClientStorePropertyInterface;

    /**
     * @param $client_store_action
     * @return ClientStorePropertyInterface
     */
    public function setDeletedAction($client_store_action): ClientStorePropertyInterface;

    /**
     * @param bool $sendable
     * @return ClientStorePropertyInterface
     */
    public function onlyIf(bool $sendable): ClientStorePropertyInterface;

    /**
     * @return bool
     */
    public function isSendable(): bool;

    /**
     * @return bool
     */
    public function isNotSendable(): bool;
}