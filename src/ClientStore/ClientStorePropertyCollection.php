<?php

namespace Akceli\RealtimeClientStoreSync\ClientStore;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ClientStorePropertyCollection implements ClientStorePropertyInterface
{
    /** @var string */
    private $resource;
    private $builder;
    private $size;

    /**
     * PusherStoreCollection constructor.
     * @param string $resource
     * @param $builder
     * @param int $size
     */
    public function __construct($builder, string $resource = null, int $size = null)
    {
        $this->resource = $resource ?? ClientStoreDefaultResource::class;
        $this->builder = $builder;
        $this->size = $size ?? config('client-store.default_pagination_size');
    }

    public function getDataFromModel(Model $model)
    {
        return (new $this->resource($model))->resolve();
    }

    public function getSingleData(int $id)
    {
        $model = $this->getBuilder()->findOrFail($id);
        return new $this->resource($model);
    }

    public function getData(Request $request)
    {
        $size = $request->get('size', $this->size);
        $page = $request->get('page');
        $total = $request->get('total');
        $after = $request->get('after');
        $after_column = $request->get('after_column', 'id');
        if ($after) {
            $collection = $this->getBuilder()->forPageAfterId($size, $after, $after_column)->paginate($size);
        } elseif ($total) {
            $collection = $this->getBuilder()->skip($total)->take($size)->get();
        } else {
            $collection = $this->getBuilder()->paginate($size, '*', 'page', $page);
        }

        return $this->resource::collection($collection);
    }

    public function getDefaultData()
    {
        return [];
    }

    /**
     * @return Builder
     */
    public function getBuilder()
    {
        if (is_callable($this->builder)) {
            $builder = $this->builder;
            return $builder();
        }

        return $this->builder;
    }
}