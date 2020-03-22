<?php

namespace Akceli\RealtimeClientStoreSync\PusherService;

use Akceli\RealtimeClientStoreSync\ClientStore\ClientStoreController;
use Illuminate\Database\Eloquent\Model;

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
            self::$queue[$model->id.':'.$class_name] = PusherServiceEvent::Updated;
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
            self::$queue[$model->id.':'.$class_name] = PusherServiceEvent::Created;
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
            self::$queue[$model->id.':'.$class_name] = PusherServiceEvent::Deleted;
        }
    }

    public static function getQueue()
    {
        return self::$queue;
    }

    public static function flushQueue()
    {
        foreach (self::getQueue() as $identifier => $change_type) {
            $parts = explode(':', $identifier);
            $id = $parts[0];
            $class = $parts[1];
            if ($instance = self::modelResolveWithTrashed($id, $class)) {
                if ($change_type === PusherServiceEvent::Created) {
                    $instance->broadcastCreatedEvents();
                }

                if ($change_type === PusherServiceEvent::Updated) {
                    $instance->broadcastUpdatedEvents();
                }

                if ($change_type === PusherServiceEvent::Deleted) {
                    $instance->broadcastDeletedEvents();
                }
            }
        }

        self::clearQueue();

        return self::$responseContent;
    }

    public static function modelResolveWithTrashed($id, $class)
    {
        $model = $class::getModel();
        return $model::query()->withTrashed()->where($model->getTable() . '.' . $model->getKeyName(), '=', $id)->first();
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
            ClientStoreController::getStore($store, $store_id)[$property]->getDataFromModel($model)
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
            ClientStoreController::getStore($store, $store_id)[$property]->getDataFromModel($model)
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
            ClientStoreController::getStore($store, $store_id)[$property]->getDataFromModel($model)
        );
    }

    public static function SetRoot(string $storeProp, int $store_id, Model $model = null)
    {
        $store = explode('.', $storeProp)[0];
        $property = explode('.', $storeProp)[1];
        if (is_null($model)) {
            $data = ClientStoreController::prepareStore(null, ClientStoreController::getStore($store, $store_id), $property);
        } else {
            $data = ClientStoreController::getStore($store, $store_id)[$property]->getDataFromModel($model);
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
