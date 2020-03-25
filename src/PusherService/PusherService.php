<?php

namespace Akceli\RealtimeClientStoreSync\PusherService;

use Akceli\RealtimeClientStoreSync\ClientStore\ClientStoreController;
use Akceli\RealtimeClientStoreSync\ClientStore\ClientStoreInterface;
use Akceli\RealtimeClientStoreSync\ClientStore\ClientStorePropertyCollection;
use Akceli\RealtimeClientStoreSync\ClientStore\ClientStoreService;
use App\ClientStore\ClientStore;
use App\ClientStore\ClientStoreModel;
use App\ClientStore\ClientStoreModelTrait;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PusherService
{
    private static array $queue = [];
    private static array $responseContent = [];

    /**
     * @param Model $model
     */
    public static function updated(Model $model)
    {
        $class_name = get_class($model);
        $currentState = self::$queue[$model->id.':'.$class_name] ?? 0;
        if (PusherServiceEvent::Updated > $currentState) {
            self::$queue[$model->id.':'.$class_name] = [
                'type' => PusherServiceEvent::Updated,
                'store_locations' => self::mapStoreLocations($model)
            ];
        }
    }

    /**
     * @param Model $model
     */
    public static function created(Model $model)
    {
        $class_name = get_class($model);
        $currentState = self::$queue[$model->id.':'.$class_name] ?? 0;
        if (PusherServiceEvent::Created > $currentState) {
            self::$queue[$model->id.':'.$class_name] = [
                'type' => PusherServiceEvent::Created,
                'store_locations' => self::mapStoreLocations($model)
            ];
        }
    }

    /**
     * @param Model $model
     */
    public static function deleted(Model $model)
    {
        $class_name = get_class($model);
        $currentState = self::$queue[$model->id.':'.$class_name] ?? 0;
        if (PusherServiceEvent::Deleted > $currentState) {
            self::$queue[$model->id.':'.$class_name] = [
                'type' => PusherServiceEvent::Deleted,
                'store_locations' => self::mapStoreLocations($model)
            ];
        }
    }

    public static function mapStoreLocations(Model $model)
    {
        return array_map(function ($location) use ($model) {
            return [
                'location' => explode(':', $location)[0],
                'channel_id' => $model[explode(':', $location)[1]]
            ];
        }, $model->store_locations ?? []);
    }

    public static function getQueue()
    {
        return self::$queue;
    }

    public static function flushQueue()
    {
        foreach (self::getQueue() as $identifier => $change_data) {
            $change_type = $change_data['type'];
            $locations = $change_data['store_locations'];
            $parts = explode(':', $identifier);
            $id = $parts[0];
            $class = $parts[1];

            foreach ($locations as $info) {
                $location = $info['location'];
                $channel_id = $info['channel_id'];
                $store = explode('.', $location)[0];
                $prop = explode('.', $location)[1];

                $clientStore = ClientStoreService::getStore($store, $channel_id)[$prop];
                $model = $clientStore->getSingleData($id);

                if ($clientStore instanceof ClientStorePropertyCollection) {
                    if ($model instanceof Model) {
                        PusherService::UpsertCollection($location, $channel_id, $model, (bool) $model);
                    } else {
                        PusherService::RemoveFromCollection($location, $channel_id, $id);
                    }
                } else {
                    PusherService::SetRoot($location, $channel_id, $model);
                }
            }
        }

        self::clearQueue();

        return self::$responseContent;
    }

    public static function getModel(string $class): Model
    {
        return $class::getModel();
    }

    public static function modelResolveWithTrashed($id, $class)
    {
        $model = self::getModel($class);
        $query = $model::query()->where($model->getTable() . '.' . $model->getKeyName(), '=', $id);

        if ($usingSoftDeletes = in_array(SoftDeletes::class, array_keys((new \ReflectionClass($class))->getTraits()))) {
            $query->withTrashed();
        }

        return $query->first();
    }

    public static function clearQueue()
    {
        self::$queue = [];
    }

    /**
     * @param string $channel
     * @param string $store
     * @param string $prop
     * @param string $method
     * @param array $data
     * @param string $apiCall
     * @param int $delay
     * @throws \Exception
     */
    public static function broadcastEvent(string $channel, string $store, string $prop, string $method, array $data, string $apiCall = null, int $delay = 0)
    {
        $payload = [
            'store' => $store,
            'prop' => $prop,
            'method' => $method,
            'channel' => $channel,
            'data' => $data,
            'api_call' => $apiCall,
            'delay' => $delay
        ];

        /**
         * This is pushed before the size check because the response is not limited by pusher
         */
        array_push(self::$responseContent, $payload);

        $size = strlen(json_encode($payload));
        if ($size > 10240) {
            if ($apiCall) {
                $payload['data'] = null;
            } elseif ($id = $data['id'] ?? null) {
                $payload['data'] = null;
                $payload['api_call'] = "client_store/{$store}/{$prop}/{$id}";
            } else {
                throw new \Exception(json_encode([
                    'Message' => 'Exceeding pusher limit, and not fallback was provided',
                    'size' => $size,
                    'payload' => $payload
                ]));
            }
        }

        return;
        event(new UpdateClientEvent($payload, 'event', [
            new Channel($channel),
        ]));
    }

    public static function UpsertCollection(string $storeProp, int $store_id, Model $model, bool $add_or_update = true)
    {
        $store = explode('.', $storeProp)[0];
        $property = explode('.', $storeProp)[1];
        PusherService::broadcastEvent(
            $store. '.' . $store_id,
            $store,
            $property,
            PusherServiceMethod::UpsertCollection($add_or_update),
            ClientStoreService::getStore($store, $store_id)[$property]->getDataFromModel($model)
        );
    }

    public static function UpdateInCollection(string $storeProp, int $store_id, Model $model)
    {
        $store = explode('.', $storeProp)[0];
        $property = explode('.', $storeProp)[1];
        PusherService::broadcastEvent(
            $store. '.' . $store_id,
            $store,
            $property,
            PusherServiceMethod::UpdateInCollection,
            ClientStoreService::getStore($store, $store_id)[$property]->getDataFromModel($model)
        );
    }

    public static function AddToCollection(string $storeProp, int $store_id, Model $model)
    {
        $store = explode('.', $storeProp)[0];
        $property = explode('.', $storeProp)[1];
        PusherService::broadcastEvent(
            $store. '.' . $store_id,
            $store,
            $property,
            PusherServiceMethod::AddToCollection,
            ClientStoreService::getStore($store, $store_id)[$property]->getDataFromModel($model)
        );
    }

    public static function SetRoot(string $storeProp, int $store_id, Model $model = null)
    {
        $store = explode('.', $storeProp)[0];
        $property = explode('.', $storeProp)[1];
        if (is_null($model)) {
            $data = ClientStoreController::prepareStore(null, ClientStoreService::getStore($store, $store_id), $property)->toArray();
        } else {
            $data = ClientStoreService::getStore($store, $store_id)[$property]->getDataFromModel($model);
        }
        PusherService::broadcastEvent(
            $store. '.' . $store_id,
            $store,
            $property,
            PusherServiceMethod::SetRoot,
            $data
        );
    }

    public static function RemoveFromCollection(string $storeProp, int $store_id, int $id)
    {
        $store = explode('.', $storeProp)[0];
        $property = explode('.', $storeProp)[1];
        PusherService::broadcastEvent(
            $store. '.' . $store_id,
            $store,
            $property,
            PusherServiceMethod::RemoveFromCollection,
            ['id' => $id]
        );
    }
}
