<?php

namespace Akceli\RealtimeClientStoreSync\ClientStore;

use Akceli\RealtimeClientStoreSync\PusherService\PusherServiceEvent;
use http\Exception\InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Class ClientStorePropertyRaw
 * @package Akceli\RealtimeClientStoreSync\ClientStore
 * @mixin ClientStorePropertyInterface
 */
class ClientStorePropertyRaw implements ClientStorePropertyInterface
{
    use ClientStorePropertyTrait;
    
    /** @var string */
    private $store;

    /** @var string */
    private $property;
    
    /** @var int  */
    private $channel_id;

    private $created_method;
    private $updated_method;
    private $deleted_method;
    private $sendable = true;

    private $data;
    private $default;

    /**
     * PusherStoreSingle constructor.
     * @param string $store
     * @param $data
     * @param $default
     */
    public function __construct(int $channel_id, string $store, string $property, $data, $default = null)
    {
        $this->channel_id = $channel_id;
        $this->store = $store;
        $this->property = $property;
        $this->data = $data;
        $this->default = $default;
        $this->resource = ClientStoreDefaultResource::class;
    }

    public function getStore(): string
    {
        return $this->store;
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function getModel()
    {
        return null;
    }

    public function getDataFromModel(Model $model = null)
    {
        $data = $this->data;
        return $data();
    }

    public function getData(Request $request = null)
    {
        $data = $this->data;
        return $data();
    }

    public function getDefaultData()
    {
        return $this->default;
    }

    public function getSingleData(int $id)
    {
        $data = $this->data;
        return $data();
    }
}