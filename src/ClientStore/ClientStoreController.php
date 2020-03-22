<?php

namespace Akceli\RealtimeClientStoreSync\ClientStore;

use Akceli\RealtimeClientStoreSync\PusherStoreInterface;
use App\ClientStore\ClientStore;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

class ClientStoreController extends Controller
{
    public static function apiRoutes()
    {
        Route::get('client_store/{store}/{store_id}/{property?}/{id?}', 'Api\ClientStoreController@getClientStoreApi');
    }

    public function getClientStoreApi(Request $request, string $store, int $store_id = null, string $property = null, int $id = null)
    {
        return $this->prepareStore($request, ClientStore::getStore($store, $store_id), $property, $id);
    }
    
    /**
     * @param Request $request
     * @param PusherStoreInterface[] $store
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
            return $store[$property]->getData();
        }

        /**
         * If not $with prop is provided, then return the entire store
         */
        $with = $request->get('with');
        if (empty($with)) {
            $with = array_keys($store);
        }

        $response = [];
        foreach ($store as $prop => $pusherStore) {
            if (in_array($prop, $with)) {
                $response[$prop] =  $pusherStore->getData();
            } else {
                /** Default to not include defaults when using $with */
                if (!$request->get('include_defaults', false)) {
                    $response[$prop] =  $pusherStore->getDefaultData();
                }
            }
        }

        return $response;
    }
}
