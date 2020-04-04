<?php

namespace Akceli\RealtimeClientStoreSync\ClientStore;

use Akceli\RealtimeClientStoreSync\PusherService\ClientStoreActions;
use Akceli\RealtimeClientStoreSync\PusherService\PusherServiceEvent;
use http\Exception\InvalidArgumentException;
use Illuminate\Database\Eloquent\Model;

trait ClientStorePropertyTrait
{
    public function setModel(Model $model = null, bool $upsert = true): ClientStorePropertyInterface
    {
        $this->model = $model;

        /**
         * set the actions if they are not already set
         */
        $this->created_method = $this->created_method ?? ClientStoreActions::UpsertOrRemoveFromCollection($upsert);
        $this->updated_method = $this->updated_method ?? ClientStoreActions::UpsertOrRemoveFromCollection($upsert);
        $this->deleted_method = $this->deleted_method ?? ClientStoreActions::UpsertOrRemoveFromCollection($upsert);

        return $this;
    }
    
    public function getModel()
    {
        return $this->model;
    }

    public static function validClientStoreActions(): array
    {
        return [
            ClientStoreActions::UpdateInCollection,
            ClientStoreActions::SetRoot,
            ClientStoreActions::PatchRoot,
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

        $this->created_method = $client_store_action;
        $this->updated_method = $client_store_action;
        $this->deleted_method = $client_store_action;
        return $this;
    }
    
    public function setCreatedAction($client_store_action): ClientStorePropertyInterface
    {
        if (!in_array($client_store_action, self::validClientStoreActions())) {
            throw new \Exception('Valid Client Store Actions are ' . json_encode(self::validClientStoreActions()));
        }
        
        $this->created_method = $client_store_action;
        return $this;
    }

    public function setUpdatedAction($client_store_action): ClientStorePropertyInterface
    {
        if (!in_array($client_store_action, self::validClientStoreActions())) {
            throw new \Exception('Valid Client Store Actions are ' . json_encode(self::validClientStoreActions()));
        }

        $this->created_method = $client_store_action;
        return $this;
    }
    public function setDeletedAction($client_store_action): ClientStorePropertyInterface
    {
        if (!in_array($client_store_action, self::validClientStoreActions())) {
            throw new \Exception('Valid Client Store Actions are ' . json_encode(self::validClientStoreActions()));
        }

        $this->created_method = $client_store_action;
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

    public function isSendable(): bool {
        return $this->sendable;
    }

    public function isNotSendable(): bool {
        return !$this->sendable;
    }
}