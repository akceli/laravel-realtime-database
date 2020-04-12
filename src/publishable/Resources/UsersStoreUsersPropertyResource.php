<?php

namespace App\Resources;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class UsersStoreUsersPropertyResource
 *
 * @property User $resource
 *
 * @package App\Http\Resources
 *
 * @example https://laravel.com/docs/6.x/eloquent-resources#concept-overview
 */
class UsersStoreUsersPropertyResource extends JsonResource
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
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'email_verified_at' => $this->resource->email_verified_at,
            'password' => $this->resource->password,
            'remember_token' => $this->resource->remember_token,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
