<?php

namespace Akceli\RealtimeClientStoreSync\ClientStore;

use Akceli\RealtimeClientStoreSync\PusherService\ClientStoreActions;
use Akceli\RealtimeClientStoreSync\PusherService\PusherService;
use Akceli\RealtimeClientStoreSync\PusherService\PusherServiceEvent;
use http\Exception\InvalidArgumentException;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait ClientStorePropertyTrait
 * @package Akceli\RealtimeClientStoreSync\ClientStore
 * @mixin ClientStorePropertyInterface
 */
trait ClientStorePropertyTrait
{
    public function setModel(Model $model = null, bool $upsert = true): ClientStorePropertyInterface
    {
        $this->model = $model;

        if ($this instanceof ClientStorePropertyCollection) {
            $defaultMethod = ClientStoreActions::UpsertOrRemoveFromCollection($upsert);
        } else {
            $defaultMethod = ClientStoreActions::SetRoot;
        }

        /**
         * set the actions if they are not already set
         */
        $this->created_method = $this->created_method ?? $defaultMethod;
        $this->updated_method = $this->updated_method ?? $defaultMethod;
        $this->deleted_method = $this->deleted_method ?? $defaultMethod;

        return $this;
    }

    public function setDirty(array $dirty_attributes = [])
    {
        $this->dirty_attributes = $dirty_attributes;
    }

    public function getDirty(): array
    {
        return $this->dirty_attributes;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function getChannelId(): int
    {
        return $this->channel_id;
    }

    public static function validClientStoreActions(): array
    {
        return [
            ClientStoreActions::SetRoot,
            ClientStoreActions::UpdateInCollection,
            ClientStoreActions::AddToCollection,
            ClientStoreActions::RemoveFromCollection,
            ClientStoreActions::UpsertCollection,
            ClientStoreActions::DoNothing,
        ];
    }

    public function setDefaultAction($client_store_action): ClientStorePropertyInterface
    {
        if (!in_array($client_store_action, self::validClientStoreActions())) {
            throw new \Exception('Valid Client Store Actions are ' . json_encode(self::validClientStoreActions()));
        }

        if ($this instanceof ClientStorePropertyCollection) {
            $this->created_method = $client_store_action;
            $this->updated_method = $client_store_action;
            $this->deleted_method = $client_store_action;
        }
        return $this;
    }

    public function setCreatedAction($client_store_action): ClientStorePropertyInterface
    {
        if (!in_array($client_store_action, self::validClientStoreActions())) {
            throw new \Exception('Valid Client Store Actions are ' . json_encode(self::validClientStoreActions()));
        }

        if ($this instanceof ClientStorePropertyCollection) {
            $this->created_method = $client_store_action;
        }
        return $this;
    }

    public function setUpdatedAction($client_store_action): ClientStorePropertyInterface
    {
        if (!in_array($client_store_action, self::validClientStoreActions())) {
            throw new \Exception('Valid Client Store Actions are ' . json_encode(self::validClientStoreActions()));
        }

        if ($this instanceof ClientStorePropertyCollection) {
            $this->updated_method = $client_store_action;
        }
        return $this;
    }
    public function setDeletedAction($client_store_action): ClientStorePropertyInterface
    {
        if (!in_array($client_store_action, self::validClientStoreActions())) {
            throw new \Exception('Valid Client Store Actions are ' . json_encode(self::validClientStoreActions()));
        }

        if ($this instanceof ClientStorePropertyCollection) {
            $this->deleted_method = $client_store_action;
        }
        return $this;
    }

    public function getEventBehavior(int $pusherEvent)
    {
        if ($pusherEvent === PusherServiceEvent::Created) {
            return ($this->created_method) ?? false;
        }
        if ($pusherEvent === PusherServiceEvent::Updated) {
            return ($this->updated_method) ?? false;
        }
        if ($pusherEvent === PusherServiceEvent::Deleted) {
            return ($this->deleted_method) ?? false;
        }

        throw new InvalidArgumentException('Only valid Pusher Event Types are ' . json_encode([
                PusherServiceEvent::Created,
                PusherServiceEvent::Updated,
                PusherServiceEvent::Deleted,
            ]));
    }

    public function onlyIf(bool $sendable): ClientStorePropertyInterface
    {
        $this->sendable = $sendable;
        return $this;
    }

    public function onlyIfDirty(array $attributes = []): ClientStorePropertyInterface
    {
        $this->onlyIf((bool) count(array_intersect($attributes, $this->dirty_attributes ?? [])));
        return $this;
    }

    public function isSendable(): bool {
        return $this->sendable;
    }

    public function isNotSendable(): bool {
        return !$this->sendable;
    }

    public function broadcast($client_store_action)
    {
        if (!in_array($client_store_action, self::validClientStoreActions())) {
            throw new \Exception('Valid Client Store Actions are ' . json_encode(self::validClientStoreActions()));
        }

        if ($client_store_action === false) {
            return;
        }

        PusherService::broadcastStoreEvent(
            $this,
            $client_store_action
        );
    }

    public function broadcastSetRoot()
    {
        PusherService::broadcastStoreEvent($this, ClientStoreActions::SetRoot);
    }

    public function broadcastUpdateInCollection()
    {
        PusherService::broadcastStoreEvent($this, ClientStoreActions::UpdateInCollection);
    }

    public function broadcastAddToCollection()
    {
        PusherService::broadcastStoreEvent($this, ClientStoreActions::AddToCollection);
    }

    public function broadcastRemoveFromCollection()
    {
        PusherService::broadcastStoreEvent($this, ClientStoreActions::RemoveFromCollection);
    }

    public function broadcastUpsertCollection()
    {
        PusherService::broadcastStoreEvent($this, ClientStoreActions::UpsertCollection);
    }

    public function broadcastUpsertOrRemoveFromCollection(bool $add_or_update)
    {
        PusherService::broadcastStoreEvent($this, ClientStoreActions::UpsertOrRemoveFromCollection($add_or_update));
    }
}