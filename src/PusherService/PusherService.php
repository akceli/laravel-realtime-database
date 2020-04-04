<?php

namespace Akceli\RealtimeClientStoreSync\PusherService;

use Akceli\RealtimeClientStoreSync\ClientStore\ClientStoreController;
use Akceli\RealtimeClientStoreSync\ClientStore\ClientStorePropertyCollection;
use Akceli\RealtimeClientStoreSync\ClientStore\ClientStorePropertyInterface;
use Akceli\RealtimeClientStoreSync\ClientStore\ClientStorePropertyRaw;
use Akceli\RealtimeClientStoreSync\ClientStore\ClientStorePropertySingle;
use Akceli\RealtimeClientStoreSync\ClientStore\ClientStoreService;
use Akceli\RealtimeClientStoreSync\Events\UpdateClientEvent;
use http\Exception\InvalidArgumentException;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Resources\Json\Resource;

class PusherService
{
    private static $queue = [];
    private static $responseContent = [];

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
                'store_properties' => $model->getStoreProperties(),
                'model' => $model,
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
                'store_properties' => $model->getStoreProperties(),
                'model' => $model,
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
                'store_properties' => $model->getStoreProperties(),
                'model' => $model,
            ];
        }
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
            $cachedModel = $change_data['model'];
            $parts = explode(':', $identifier);
            $id = $parts[0];
            $class = $parts[1];

            /** @var ClientStorePropertyInterface $storeProperty */
            foreach ($change_data['store_properties'] ?? [] as $storeProperty) {
                /**
                 * If not sendable then skip
                 */
                if ($storeProperty->isNotSendable()) {
                    continue;
                }

                if ($model = $storeProperty->getModel()) {
                    /**
                     * If Model is set the use the event behavior to determine the
                     */
                    PusherService::broadcastEvent(
                        $channel_id,
                        $storeProp->getStore(),
                        $storeProp->getProperty(),
                        $storeProperty->getEventBehavior($change_type),
                        $storeProp->getDataFromModel($storeProperty->getModel())
                    );
                } else {
                    /**
                     * Should only leverage this if the model is the same as the Eloquen Query Builder Model
                     */
                    if ($storeProperty instanceof ClientStorePropertyCollection) {
                        $storeProperty->validateModelIsForStore($cachedModel);
                        if ($model = $storeProperty->getBuilder()->find($id)) {
                            PusherService::UpsertCollection($storeProperty, $channel_id, $model);
                        } else {
                            PusherService::RemoveFromCollection($storeProperty, $channel_id, $id);
                        }
                    } elseif ($storeProperty instanceof ClientStorePropertySingle) {
                        $storeProperty->validateModelIsForStore($cachedModel);
                        $model = $storeProperty->getBuilder()->find($id);
                        PusherService::SetRoot($storeProperty, $channel_id, $model);
                    } elseif ($storeProperty instanceof ClientStorePropertyRaw) {
                        PusherService::SetRoot($storeProperty, $channel_id);
                    }
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
    public static function broadcastEvent(ClientStorePropertyInterface $storeProperty, string $channel_id, string $method, array $data, string $apiCall = null, int $delay = 0)
    {
        $payload = [
            'store' => $storeProperty->getStore(),
            'prop' => $storeProperty->getProperty(),
            'method' => $method,
            'channel_id' => $channel_id,
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
                $payload['api_call'] = config('client-store.api_path') . "/{$store}/{$prop}/{$id}";
            } else {
                throw new \Exception(json_encode([
                    'Message' => 'Exceeding pusher limit, and not fallback was provided',
                    'size' => $size,
                    'payload' => $payload
                ]));
            }
        }

        event(new UpdateClientEvent($payload, 'event', [
            new Channel($channel),
        ]));
    }

    public static function UpsertCollection(ClientStorePropertyInterface $storeProp, int $channel_id, Model $model, bool $add_or_update = true)
    {
        PusherService::broadcastEvent(
            $channel_id,
            $storeProp->getStore(),
            $storeProp->getProperty(),
            ClientStoreActions::UpsertOrRemoveFromCollection($add_or_update),
            $storeProp->getDataFromModel($model)
        );
    }

    public static function UpdateInCollection(ClientStorePropertyInterface $storeProp, int $channel_id, Model $model)
    {
        PusherService::broadcastEvent(
            $channel_id,
            $storeProp->getStore(),
            $storeProp->getProperty(),
            ClientStoreActions::UpdateInCollection,
            $storeProp->getDataFromModel($model)
        );
    }

    public static function AddToCollection(ClientStorePropertyInterface $storeProp, int $channel_id, Model $model)
    {
        PusherService::broadcastEvent(
            $channel_id,
            $storeProp->getStore(),
            $storeProp->getProperty(),
            ClientStoreActions::AddToCollection,
            $storeProp->getDataFromModel($model)
        );
    }

    public static function SetRoot(ClientStorePropertyInterface $storeProp, int $channel_id, Model $model = null)
    {
        $store = $storeProp->getStore();
        $property = $storeProp->getProperty();
        if ($model) {
            $data = $storeProp->getDataFromModel($model);
        } else {
            $data = $storeProp->getData();
            if ($data instanceof Resource) {
                $data = $data->resolve();
            }
        }
        PusherService::broadcastEvent(
            $channel_id,
            $storeProp->getStore(),
            $storeProp->getProperty(),
            ClientStoreActions::SetRoot,
            $data
        );
    }

    public static function RemoveFromCollection(ClientStorePropertyInterface $storeProp, int $channel_id, int $id)
    {
        PusherService::broadcastEvent(
            $channel_id,
            $storeProp->getStore(),
            $storeProp->getProperty(),
            ClientStoreActions::RemoveFromCollection,
            ['id' => $id]
        );
    }
}
