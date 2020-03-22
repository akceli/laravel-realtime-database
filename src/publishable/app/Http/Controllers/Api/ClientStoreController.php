<?php

namespace App\Http\Controllers\Api;

use Akceli\RealtimeClientStoreSync\PusherStoreInterface;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;

class ClientStoreController extends Controller
{
    public static function apiRoutes()
    {
        Route::get('client_store/{store}/{store_id}/{property?}/{id?}', 'Api\ClientStoreController@getClientStoreApi');
    }

    public function getClientStoreApi(Request $request, string $store, int $store_id = null, string $property = null, int $id = null)
    {
        return $this->prepareStore($request, self::getStore($store, $store_id), $property, $id);
    }

    /**
     * @param $store
     * @param int $store_id
     * @return array|PusherStoreInterface[]
     */
    public static function getStore($store, int $store_id): array
    {
        $stores = [
            'account' => self::accountStore($store_id),
            'market' => self::marketStore($store_id),
            'activeRecord' => self::RecordStore($store_id),
            'fus' => self::followUpSequenceStore($store_id),
        ];

        if (in_array($store, array_keys($stores))) {
            return $stores[$store];
        } else {
            throw new \InvalidArgumentException('Invalid Store Selection. available stores are [' . implode(', ', array_keys($stores)) . ']');
        }
    }

    /**
     * @param int $account_id
     * @return array|PusherStoreInterface[]
     */
    public static function accountStore(int $account_id): array
    {
        return [
            'account' => new PusherStoreSingle(AccountStoreAccountResource::class,
                Account::query()->where('id', '=', $account_id)
            ),
            'statuses' => new PusherStoreCollection(AccountStoreStatusResource::class,
                Status::query()
            ),
            'users' => new PusherStoreCollection(AccountStoreUserResource::class,
                User::withTrashed()
            ),
            'customFields' => new PusherStoreCollection(AccountStoreCustomFieldResource::class,
                CustomField::withTrashed()
            ),
            'snippets' => new PusherStoreCollection(AccountStoreSnippetResource::class,
                Snippet::query()
            ),
            'fusTemplates' => new PusherStoreCollection(AccountStoreFollowUpSequenceTemplateResource::class,
                FollowUpSequenceTemplate::query()
            ),
        ];
    }

    /**
     * @param int $market_id
     * @return array|PusherStoreInterface[]
     */
    public static function marketStore(int $market_id): array
    {
        return [
            'market' => new PusherStoreSingle(AccountStoreMarketResource::class,
                Market::query()
                    ->where('id', '=', $market_id)
            ),
            'badges' => new PusherStoreRaw(function() {
                return [
                    'orphaned_records_count' => Record::orphaned()->count(),
                    'past_due_follow_up_scheduled_contacts_count' => ScheduledContact::whereIncomplete()->whereHas('followUpSequenceStep.followUpSequence', function (Builder $builder) {
                        $builder->whereNotNull('completed_at');
                    })->count()
                ];
            }, ['orphaned_records_count' => 0, 'past_due_follow_up_scheduled_contacts_count' => 0]),
            'pipeline' => new PusherStoreCollection(MarketStorePipelineResource::class,
                RecordStatus::whereActive()->with([
                    'checklist.checklistItems',
                    'record.property',
                    'record.contact',
                    'record.users',
                    'record.incompleteScheduledContacts',
                    'record.pipelineAppointments'
                ])
            ),
            'campaigns' => new PusherStoreCollection(MarketStoreCampaignResource::class,
                Campaign::query()
                    ->with(['records:actual_profit', 'campaignCosts:amount'])
            ),
            'integrations' => new PusherStoreCollection(MarketStoreIntegrationResource::class,
                Integration::query()
            ),
            'integrationPhoneNumbers' => new PusherStoreCollection(MarketStoreIntegrationPhoneNumberResource::class,
                IntegrationPhoneNumber::query()
            ),
            'unaddressedInboundCommunications' => new PusherStoreCollection(MarketStoreCommunicationLogResource::class,
                CommunicationLog::query()
                    ->unAddressed()
                    ->inbound()
            ),
            'incompleteScheduledContacts' => new PusherStoreCollection(MarketStoreScheduledContactResource::class,
                ScheduledContact::incomplete()
            ),
        ];
    }

    /**
     * @param int $record_id
     * @return array|PusherStoreInterface[]
     */
    public static function recordStore(int $record_id): array
    {
        $record = Record::query()->where('id', '=', $record_id);
        return [
            'record' => new PusherStoreSingle(RecordStoreRecordResource::class,
                Record::query()
                    ->where('id', '=', $record_id)
            ),
            'recordStatuses' => new PusherStoreCollection(RecordStoreRecordStatusResource::class,
                RecordStatus::query()
                    ->whereActive()
                    ->where('record_id', '=', $record_id)
            ),
            'userIds' => new PusherStoreRaw(fn() =>
                $record->first()
                    ->users()
                    ->pluck('id')
                    ->toArray()
            , []),
            'checklists' => new PusherStoreCollection(RecordStoreChecklistResource::class,
                Checklist::query()
                    ->where('record_id', '=', $record_id)
            ),
            'checklistItems' => new PusherStoreCollection(RecordStoreChecklistItemResource::class,
                ChecklistItem::query()
                    ->where('record_id', '=', $record_id)
            ),
            'followUpSequences' => new PusherStoreCollection(RecordStoreFollowUpSequenceResource::class,
                FollowUpSequence::query()
                    ->where('record_id', '=', $record_id)
            ),
            'followUpSequenceSteps' => new PusherStoreCollection(RecordStoreFollowUpSequenceStepResource::class,
                FollowUpSequenceStep::query()
                    ->where('record_id', '=', $record_id)
            ),
            'buyingOffers' => new PusherStoreCollection(RecordStoreBuyingOfferResource::class,
                BuyingOffer::query()
                    ->where('record_id', '=', $record_id)
            ),
            'analysis' => new PusherStoreCollection(RecordStoreAnalysisResource::class,
                Analysis::query()
                    ->where('record_id', '=', $record_id)
            ),
            'appointments' => new PusherStoreCollection(RecordStoreAppointmentResource::class,
                Appointment::query()
                    ->where('record_id', '=', $record_id)
            ),
            'scheduledContacts' => new PusherStoreCollection(RecordStoreScheduledContactResource::class,
                ScheduledContact::query()
                    ->where('scheduled_contacts.record_id', '=', $record_id)
            ),
            'notes' => new PusherStoreCollection(RecordStoreNoteResource::class,
                Note::query()
                    ->where('record_id', '=', $record_id)
            ),
            'communicationLogs' => new PusherStoreCollection(RecordStoreCommunicationLogResource::class,
                CommunicationLog::query()
                    ->whereHas('contacts.record', function (Builder $record) use ($record_id) {
                        $record->where('id', '=', $record_id);
                    })
            ),
            'activities' => new PusherStoreCollection(RecordStoreActivityResource::class,
                Activity::query()
                    ->where('record_id', '=', $record_id)
            ),
        ];
    }

    /**
     * @param int $account_id
     * @return array|PusherStoreInterface[]
     */
    public static function followUpSequenceStore(int $account_id): array
    {
        return [
            'orphanedRecords' => new PusherStoreCollection(FollowUpSequenceStoreRecordResource::class,
                Record::query()
                    ->orphaned()
                    ->with(['property', 'contact', 'activeFollowUpSequence'])
            ),
            'scheduledContacts' => new PusherStoreCollection(FollowUpSequenceStoreScheduledContactResource::class,
                ScheduledContact::query()
                    ->whereIncomplete()
                    ->whereIsFollowUpSequenceScheduledContact()
                    ->with(['record.property', 'record.contact', 'record.activeFollowUpSequence'])
            ),
            'fusStepTemplates' => new PusherStoreCollection(FollowUpSequenceStoreFollowUpSequenceStepTemplateResource::class,
                FollowUpSequenceStepTemplate::query()
            ),
        ];
    }

    public function getInitialStoreEnums()
    {
        return [
            'buyingOfferMadeByEnum' => BuyingOfferMadeByEnum::clientData(),
            'buyingOfferStatusEnum' => BuyingOfferStatusEnum::clientData(),
            'buyingOfferTypeEnum' => BuyingOfferTypeEnum::clientData(),
            'campaignChannelEnum' => CampaignChannelEnum::clientData(),
            'campaignInboundChannelEnum' => CampaignInboundChannelEnum::clientData(),
            'campaignOutboundChannelEnum' => CampaignOutboundChannelEnum::clientData(),
            'customFieldableTypeEnum' => CustomFieldableTypeEnum::clientData(),
            'customFieldTypeEnum' => CustomFieldTypeEnum::clientData(),
            'exitStrategyEnum' => ExitStrategyEnum::clientData(),
            'sellerMotivationEnum' => SellerMotivationEnum::clientData(),
            'temperatureEnum' => TemperatureEnum::clientData(),
            'recordMotivationEnum' => MotivationEnum::clientData(),
            'recordArchiveTypeEnum' => RecordArchiveTypeEnum::clientData(),
            'followUpSequenceStatusEnum' => FollowUpSequenceStatusEnum::clientData(),
            'followUpSequenceTemplateStatusEnum' => FollowUpSequenceTemplateStatusEnum::clientData(),
            'followUpSequenceStepTypeEnum' => FollowUpSequenceStepTypeEnum::clientData(),
            'followUpSequenceStepStatusEnum' => FollowUpSequenceStepStatusEnum::clientData(),
            'userDefinedFollowUpSequenceStepTypeEnum' => UserDefinedFollowUpSequenceStepTypeEnum::clientData(),
            'communicationTypeEnum' => CommunicationTypeEnum::clientData(),
            'activityActionEnum' => ActivityActionEnum::clientData(),
            'activityTypeEnum' => ActivityTypeEnum::clientData(),
            'appointmentCancellationTypeEnum' => AppointmentCancellationTypeEnum::clientData(),
            'communicationDirectionEnum' => CommunicationDirectionEnum::clientData(),
            'statusTypeEnum' => StatusTypeEnum::clientData(),
            'snippetTypeEnum' => SnippetTypeEnum::clientData(),
            'importStatusEnum' => ImportStatusEnum::clientData(),
            'shortcodes' => ShortcodeService::getClientShortcodes(),
        ];
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
                /** Default to include defaults when using $with */
                if (!$request->get('omit_defaults', false)) {
                    $response[$prop] =  $pusherStore->getDefaultData();
                }
            }
        }

        return $response;
    }
}
