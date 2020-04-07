<?php

namespace Akceli\RealtimeClientStoreSync\ClientStore;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientStoreController extends Controller
{
    public static function apiRoutes()
    {
        Route::get('{store}/{store_id}/{property?}/{id?}', function (Request $request, string $store, $store_id = null, string $property = null, int $id = null) {
            return self::prepareStore($request, ClientStoreService::getStore($store, (int) $store_id), $property, $id);
        });
    }
    
    /**
     * @param Request $request
     * @param ClientStorePropertyInterface[] $store
     * @param string $property = null
     * @param int $id = null
     * @return array
     */
    public static function prepareStore($request, array $store, string $property = null, int $id = null)
    {
        if ($id) {
            return $store[$property]->getSingleData($id);
        }

        if ($property) {
            return $store[$property]->getData($request);
        }

        /**
         * If not $with prop is provided, then return the entire store
         */
        $with = $request->get('with');
        if (empty($with)) {
            $with = array_keys($store);
        } else {
            $with = explode(',', $with);
        }

        $response = [];
        foreach ($store as $prop => $pusherStore) {
            if (in_array($prop, $with)) {
                $data = $pusherStore->getData($request);
                if ($data instanceof AnonymousResourceCollection) {
                    $data =  $data->toResponse($request)->getData();
                }
                $response[$prop] =  $data;
            } else {
                $data = $pusherStore->getDefaultData();
                if ($data instanceof AnonymousResourceCollection) {
                    $data =  $data->toResponse($request)->getData();
                }
                $response[$prop] =  $data;
            }
        }

        return $response;
    }
}
