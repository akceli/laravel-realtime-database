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
    public static $processedChanges = [];
    private static $responseContent = [];
    public static $tracking = true;
    
    public static function disableTracking()
    {
        self::$tracking = false;
    }
    
    public static function enableTracking()
    {
        self::$tracking = true;
    }

    /**
     * @param Model $model
     */
    public static function updated(Model $model)
    {
        if (!self::$tracking) {
            return;
        }

        $class_name = get_class($model);
        $current_state = self::$queue[$model->id.':'.$class_name]['type'] ?? 0;
        if (PusherServiceEvent::Updated > $current_state) {
            self::$queue[$model->id.':'.$class_name] = [
                'type' => PusherServiceEvent::Updated,
                'store_properties' => $model->getStoreProperties(),
                'model' => $model,
                'dirty_attributes' => $model->getDirty()
            ];
        }
    }

    /**
     * @param Model $model
     */
    public static function created(Model $model, array $dirty = [])
    {
        if (!self::$tracking) {
            return;
        }

        $class_name = get_class($model);
        $current_state = self::$queue[$model->id.':'.$class_name]['type'] ?? 0;
        if (PusherServiceEvent::Created > $current_state) {
            self::$queue[$model->id.':'.$class_name] = [
                'type' => PusherServiceEvent::Created,
                'store_properties' => $model->getStoreProperties(),
                'model' => $model,
                'dirty_attributes' => $dirty
            ];
        }
    }

    /**
     * @param Model $model
     */
    public static function deleted(Model $model)
    {
        if (!self::$tracking) {
            return;
        }

        $class_name = get_class($model);
        $current_state = self::$queue[$model->id.':'.$class_name]['type'] ?? 0;
        if (PusherServiceEvent::Deleted > $current_state) {
            self::$queue[$model->id.':'.$class_name] = [
                'type' => PusherServiceEvent::Deleted,
                'store_properties' => $model->getStoreProperties(),
                'model' => $model,
                'dirty_attributes' => $model->getDirty()
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
            $cachedmodel = $change_data['model'];
            $dirty_attributes = $change_data['dirty_attributes'];
            $id = explode(':', $identifier)[0];

            /** @var ClientStorePropertyInterface $storeProperty */
            foreach ($change_data['store_properties'] ?? [] as $storeProperty) {
                $storeProperty->setDirty($dirty_attributes);
                $changes = $storeProperty->getStore() .'.'. $storeProperty->getChannelId() .'.'. $storeProperty->getProperty() .'.'. $id;

                /**
                 * If channel already processed dont re process it
                 */
                if (self::$processedChanges[$changes] ?? false) {
                    continue;
                } else {
                    self::$processedChanges[$changes] = true;
                }

                /**
                 * if not sendable then skip
                 */
                if ($storeProperty->isNotSendable()) {
                    continue;
                }
                
                if ($model = $storeProperty->getModel()) {
                    /**
                     * if model is set the use the event behavior to determine the
                     */
                    if ($method = $storeProperty->getEventBehavior($change_type)) {
                        PusherService::broadcastStoreEvent(
                            $storeProperty,
                            $method
                        );
                    }
                } else {
                    /**
                     * should only leverage this if the model is the same as the eloquen query builder model
                     */
                    if ($storeProperty instanceof ClientStorePropertyCollection) {
                        $storeProperty->validateModelIsForStore($cachedmodel);
                        if ($model = $storeProperty->getBuilder()->find($id)) {
                            PusherService::UpsertCollection($storeProperty, $model);
                        } else {
                            PusherService::RemoveFromCollection($storeProperty, $id);
                        }
                    } elseif ($storeProperty instanceof ClientStorePropertySingle) {
                        $storeProperty->validateModelIsForStore($cachedmodel);
                        $model = $storeProperty->getBuilder()->find($id);
                        PusherService::SetRoot($storeProperty, $model);
                    } elseif ($storeProperty instanceof ClientStorePropertyRaw) {
                        PusherService::SetRoot($storeProperty);
                    }
                }
            }
        }

        $responseContent = self::$responseContent;

        self::clearQueue();

        return $responseContent;
    }

    public static function getModel(string $class): Model
    {
        return $class::getModel();
    }

    public static function clearQueue()
    {
        self::$queue = [];
        self::$processedChanges = [];
        self::$responseContent = [];
    }

    /**
     * @param ClientStorePropertyInterface $storeProperty
     * @param string $method
     * @param int $delay
     * @throws \exception
     */
    public static function broadcastStoreEvent(ClientStorePropertyInterface $storeProperty, string $method, int $delay = 0)
    {
        if ($storeProperty->isNotSendable()) {
            return;
        }

        self::broadcastEvent(
            $storeProperty->getStore(),
            $storeProperty->getProperty(),
            $storeProperty->getChannelId(),
            $method,
            $storeProperty->getDataFromModel($storeProperty->getModel()),
            $delay
        );
    }

    /**
     * @param string $store
     * @param string $property
     * @param string $channel_id
     * @param string $method
     * @param array $data
     * @param string $apicall
     * @param int $delay
     * @throws \exception
     */
    public static function broadcastEvent(string $store, string $property, string $channel_id, string $method, array $data, string $apicall = null, int $delay = 0)
    {
        $payload = [
            'store' => $store,
            'prop' => $property,
            'method' => $method,
            'channel_id' => $channel_id,
            'data' => $data,
            'api_call' => $apicall,
            'delay' => $delay
        ];

        /**
         * this is pushed before the size check because the response is not limited by pusher
         */
        array_push(self::$responseContent, $payload);

        $size = strlen(json_encode($payload));
        if ($size > 10240) {
            if ($apicall) {
                $payload['data'] = null;
            } elseif ($id = $data['id'] ?? null) {
                $payload['data'] = null;
                $payload['api_call'] = config('client-store.api_path') . "/{$store}/{$property}/{$id}";
            } else {
                throw new \exception(json_encode([
                    'message' => 'exceeding pusher limit, and not fallback was provided',
                    'size' => $size,
                    'payload' => $payload
                ]));
            }
        }

        event(new UpdateClientEvent($payload, 'event', [
            new Channel($store . '.' . $channel_id),
        ]));
    }

    public static function UpsertCollection(ClientStorePropertyInterface $storeProp, Model $model, bool $add_or_update = true)
    {
        PusherService::broadcastStoreEvent(
            $storeProp->setModel($model),
            ClientStoreActions::UpsertOrRemoveFromCollection($add_or_update)
        );
    }

    public static function UpdateInCollection(ClientStorePropertyInterface $storeProp, Model $model)
    {
        PusherService::broadcastStoreEvent(
            $storeProp->setModel($model),
            ClientStoreActions::UpdateInCollection
        );
    }

    public static function AddToCollection(ClientStorePropertyInterface $storeProp, Model $model)
    {
        PusherService::broadcastStoreEvent(
            $storeProp->setModel($model),
            ClientStoreActions::AddToCollection
            );
    }

    public static function SetRoot(ClientStorePropertyInterface $storeProp, Model $model = null)
    {
        PusherService::broadcastStoreEvent(
            $storeProp->setModel($model),
            ClientStoreActions::SetRoot
        );
    }

    public static function RemoveFromCollection(ClientStorePropertyInterface $storeProp, int $id)
    {
        PusherService::broadcastEvent(
            $storeProp->getStore(),
            $storeProp->getProperty(),
            $storeProp->getChannelId(),
            ClientStoreActions::RemoveFromCollection,
            ['id' => $id]
        );
    }
}
