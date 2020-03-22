<?php

namespace Akceli\RealtimeClientStoreSync;

use App\Events\UpdateClientEvent;
use App\Http\Controllers\Api\ClientStoreController;
use App\Models\Enums\PusherServiceEventTypeEnum;
use App\Models\Enums\PusherServiceMethodEnum;
use App\Services\ExceptionService;
use App\Services\PolyMorphicResolverService;
use App\Traits\BaseAccountModelTrait;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;

class PusherService
{
    private static array $queue = [];
    private static array $responseContent = [];

    /**
     * @param Model|BaseAccountModelTrait $model
     */
    public static function updated(Model $model)
    {
        $currentState = self::$queue[$model->id.':'.$model->class_name] ?? 0;
        if (PusherServiceEventTypeEnum::Updated > $currentState) {
            self::$queue[$model->id.':'.$model->class_name] = PusherServiceEventTypeEnum::Updated;
        }
    }

    /**
     * @param Model|BaseAccountModelTrait $model
     */
    public static function created(Model $model)
    {
        $currentState = self::$queue[$model->id.':'.$model->class_name] ?? 0;
        if (PusherServiceEventTypeEnum::Created > $currentState) {
            self::$queue[$model->id.':'.$model->class_name] = PusherServiceEventTypeEnum::Created;
        }
    }

    /**
     * @param Model|BaseAccountModelTrait $model
     */
    public static function deleted(Model $model)
    {
        $currentState = self::$queue[$model->id.':'.$model->class_name] ?? 0;
        if (PusherServiceEventTypeEnum::Deleted > $currentState) {
            self::$queue[$model->id.':'.$model->class_name] = PusherServiceEventTypeEnum::Deleted;
        }
    }

    public static function getQueue()
    {
        return self::$queue;
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
                $payload['api_call'] = "v1/client_store/{$store}/{$prop}/{$id}";
            } else {
                ExceptionService::report(new \Exception(json_encode([
                    'Message' => 'Exceeding pusher limit, and not fallback was provided',
                    'size' => $size,
                    'payload' => $payload
                ])));
            }
        }

        event(new UpdateClientEvent($payload, 'event', [
            new Channel($channel),
        ]));
    }

    public static function flushQueue()
    {
        foreach (self::getQueue() as $identifier => $change_type) {
            [$id, $class] = explode(':', $identifier);
            if (in_array(PusherBroadcasterInterface::class, class_implements($class))) {
                if ($instance = PolyMorphicResolverService::resolveWithTrashed($id, $class)) {
                    if ($change_type === PusherServiceEventTypeEnum::Created) {
                        $instance->broadcastCreatedEvents();
                    }

                    if ($change_type === PusherServiceEventTypeEnum::Updated) {
                        $instance->broadcastUpdatedEvents();
                    }

                    if ($change_type === PusherServiceEventTypeEnum::Deleted) {
                        $instance->broadcastDeletedEvents();
                    }
                }
            }
        }

        self::clearQueue();

        return self::$responseContent;
    }

    public static function clearQueue()
    {
        self::$queue = [];
    }

    public static function UpsertCollection(string $storeProp, int $store_id, Model $model, bool $add_or_update = true)
    {
        [$store, $property] = explode('.', $storeProp);
        PusherService::broadcastEvent(
            $store. '.' . $store_id,
            $store, $property,
            PusherServiceMethodEnum::UpsertCollection($add_or_update),
            ClientStoreController::getStore($store, $store_id)[$property]->getDataFromModel($model)
        );
    }

    public static function UpdateInCollection(string $storeProp, int $store_id, Model $model)
    {
        [$store, $property] = explode('.', $storeProp);
        PusherService::broadcastEvent(
            $store. '.' . $store_id,
            $store, $property,
            PusherServiceMethodEnum::UpdateInCollection,
            ClientStoreController::getStore($store, $store_id)[$property]->getDataFromModel($model)
        );
    }

    public static function AddToCollection(string $storeProp, int $store_id, Model $model)
    {
        [$store, $property] = explode('.', $storeProp);
        PusherService::broadcastEvent(
            $store. '.' . $store_id,
            $store, $property,
            PusherServiceMethodEnum::AddToCollection,
            ClientStoreController::getStore($store, $store_id)[$property]->getDataFromModel($model)
        );
    }

    public static function SetRoot(string $storeProp, int $store_id, Model $model = null)
    {
        [$store, $property] = explode('.', $storeProp);
        if (is_null($model)) {
            $data = ClientStoreController::prepareStore(null, ClientStoreController::getStore($store, $store_id), $property);
        } else {
            $data = ClientStoreController::getStore($store, $store_id)[$property]->getDataFromModel($model);
        }
        PusherService::broadcastEvent(
            $store. '.' . $store_id,
            $store, $property,
            PusherServiceMethodEnum::SetRoot,
            $data
        );
    }

    public static function RemoveFromCollection(string $storeProp, int $store_id, int $id)
    {
        [$store, $property] = explode('.', $storeProp);
        PusherService::broadcastEvent(
            $store. '.' . $store_id,
            $store, $property,
            PusherServiceMethodEnum::RemoveFromCollection,
            ['id' => $id]
        );
    }
}
