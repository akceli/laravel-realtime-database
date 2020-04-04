<?php

namespace Akceli\RealtimeClientStoreSync\ClientStore;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ClientStoreDefaultResource
 *
 * @package App\Http\Resources
 *
 * @example https://laravel.com/docs/6.x/eloquent-resources#concept-overview
 */
class ClientStoreDefaultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     *
     * @return array
     */
    public function toArray($request)
    {
        if (is_array($this->resource)) {
            return $this->resource;
        }

        return $this->resource->toArray();
    }
}
