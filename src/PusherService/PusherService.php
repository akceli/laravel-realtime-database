<?php

namespace Akceli\RealtimeClientStoreSync\PusherService;

use Akceli\RealtimeClientStoreSync\ClientStore\ClientStoreController;
use Akceli\RealtimeClientStoreSync\ClientStore\ClientStorePropertyCollection;
use Akceli\RealtimeClientStoreSync\ClientStore\ClientStorePropertyInterface;
use Akceli\RealtimeClientStoreSync\ClientStore\ClientStorePropertyRaw;
use Akceli\RealtimeClientStoreSync\ClientStore\ClientStorePropertySingle;
use Akceli\RealtimeClientStoreSync\ClientStore\ClientStoreService;
use App\ClientStores\MarketStore;
use App\Events\UpdateClientEvent;
use App\User;
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
                if ($eventBehavior = $storeProperty->getEventBehavior($change_type)) {
                    $eagerLoads = array_keys($storeProperty->getBuilder()->getEagerLoads());
                    
                    /** Default behavior */
                    if ($eventBehavior === true) {
                        if ($storeProperty instanceof ClientStorePropertyCollection) {
                            if ($model = $storeProperty->getBuilder()->find($id)) {
                                PusherService::UpsertCollection($storeProperty, $channel_id, $model);
                            } else {
                                PusherService::RemoveFromCollection($storeProperty, $channel_id, $id);
                            }
                        } elseif ($storeProperty instanceof ClientStorePropertySingle) {
                            $model = $storeProperty->getBuilder()->find($id);
                            PusherService::SetRoot($storeProperty, $channel_id, $model);
                        } elseif ($storeProperty instanceof ClientStorePropertyRaw) {
                            PusherService::SetRoot($storeProperty, $channel_id);
                        }
                    }

                    /**
                     * Need to load in stuff for the cached models
                     */

                    if ($eventBehavior === ClientStoreService::CreatedEvent) {
                        if ($storeProperty instanceof ClientStorePropertyCollection) {
                            PusherService::UpsertCollection($storeProperty, $channel_id, $cachedModel);
                        } elseif ($storeProperty instanceof ClientStorePropertySingle) {
                            PusherService::SetRoot($storeProperty, $channel_id, $cachedModel);
                        } elseif ($storeProperty instanceof ClientStorePropertyRaw) {
                            PusherService::SetRoot($storeProperty, $channel_id);
                        }
                    }
                    if ($eventBehavior === ClientStoreService::UpdatedEvent) {
                        if ($storeProperty instanceof ClientStorePropertyCollection) {
                            PusherService::UpdateInCollection($storeProperty, $channel_id, $cachedModel);
                        } elseif ($storeProperty instanceof ClientStorePropertySingle) {
                            PusherService::SetRoot($storeProperty, $channel_id, $cachedModel);
                        } elseif ($storeProperty instanceof ClientStorePropertyRaw) {
                            PusherService::SetRoot($storeProperty, $channel_id);
                        }
                    }
                    if ($eventBehavior === ClientStoreService::DeletedEvent) {
                        if ($storeProperty instanceof ClientStorePropertyCollection) {
                            PusherService::RemoveFromCollection($storeProperty, $channel_id, $cachedModel->id);
                        } elseif ($storeProperty instanceof ClientStorePropertySingle) {
                            PusherService::SetRoot($storeProperty, $channel_id, $cachedModel);
                        } elseif ($storeProperty instanceof ClientStorePropertyRaw) {
                            PusherService::SetRoot($storeProperty, $channel_id);
                        }
                    }
                }
            }
//            
//            
//            
//            
//            
//            
//            
//            /** @var ClientStorePropertyInterface $storeProperty */
//            foreach ($change_data['store_properties'] ?? [] as $storeProperty) {
//                if ($eventBehavior = $storeProperty->getEventBehavior($change_type)) {
//                    if ($storeProperty instanceof ClientStorePropertyCollection) {
//                        if ($model = $storeProperty->getBuilder()->find($id)) {
//                            PusherService::UpsertCollection($storeProperty, $channel_id, $model);
//                        } else {
//                            PusherService::RemoveFromCollection($storeProperty, $channel_id, $id);
//                        }
//                    } elseif ($storeProperty instanceof ClientStorePropertySingle) {
//                        $model = $storeProperty->getBuilder()->find($id);
//                        PusherService::SetRoot($storeProperty, $channel_id, $model);
//                    } elseif ($storeProperty instanceof ClientStorePropertyRaw) {
//                        PusherService::SetRoot($storeProperty, $channel_id);
//                    }
//                }
//            }
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
    public static function broadcastEvent(string $channel_id, string $store, string $prop, string $method, array $data, string $apiCall = null, int $delay = 0)
    {
        $payload = [
            'store' => $store,
            'prop' => $prop,
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
                $payload['api_call'] = "new-store/{$store}/{$prop}/{$id}";
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
            PusherServiceMethod::UpsertCollection($add_or_update),
            $storeProp->getDataFromModel($model)
        );
    }

    public static function UpdateInCollection(ClientStorePropertyInterface $storeProp, int $channel_id, Model $model)
    {
        PusherService::broadcastEvent(
            $channel_id,
            $storeProp->getStore(),
            $storeProp->getProperty(),
            PusherServiceMethod::UpdateInCollection,
            $storeProp->getDataFromModel($model)
        );
    }

    public static function AddToCollection(ClientStorePropertyInterface $storeProp, int $channel_id, Model $model)
    {
        PusherService::broadcastEvent(
            $channel_id,
            $storeProp->getStore(),
            $storeProp->getProperty(),
            PusherServiceMethod::AddToCollection,
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
            PusherServiceMethod::SetRoot,
            $data
        );
    }

    public static function RemoveFromCollection(ClientStorePropertyInterface $storeProp, int $channel_id, int $id)
    {
        PusherService::broadcastEvent(
            $channel_id,
            $storeProp->getStore(),
            $storeProp->getProperty(),
            PusherServiceMethod::RemoveFromCollection,
            ['id' => $id]
        );
    }
}
