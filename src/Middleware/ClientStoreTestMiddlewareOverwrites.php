<?php

namespace Akceli\RealtimeClientStoreSync\Middleware;

use Akceli\RealtimeClientStoreSync\PusherService\PusherService;
use Illuminate\Testing\TestResponse;
use function GuzzleHttp\Psr7\build_query;

trait ClientStoreTestMiddlewareOverwrites
{
    public function getJson($uri, array $headers = [])
    {
        PusherService::clearQueue();
        return $this->formatResponseToHandlePusherMiddleWare(parent::getJson($uri, $headers));
    }

    public function putJson($uri, array $data = [], array $headers = [])
    {
        PusherService::clearQueue();
        return $this->formatResponseToHandlePusherMiddleWare(parent::putJson($uri, $data, $headers));
    }

    public function postJson($uri, array $data = [], array $headers = [])
    {
        PusherService::clearQueue();
        return $this->formatResponseToHandlePusherMiddleWare(parent::postJson($uri, $data, $headers));
    }

    public function patchJson($uri, array $data = [], array $headers = [])
    {
        PusherService::clearQueue();
        return $this->formatResponseToHandlePusherMiddleWare(parent::patchJson($uri, $data, $headers));
    }

    public function deleteJson($uri, array $data = [], array $headers = [])
    {
        PusherService::clearQueue();
        return $this->formatResponseToHandlePusherMiddleWare(parent::deleteJson($uri, $data, $headers));
    }

    /**
     * This is here to account for the middle ware that is used for pusher, so that the tests are easier to write.
     *
     * @param TestResponse $response
     * @return TestResponse
     */
    private function formatResponseToHandlePusherMiddleWare(TestResponse $response)
    {
        try {
            if ($content = json_decode($response->getContent(), true)) {
                if (isset($content['responseData'])) {
                    if (is_array($content['responseData'])) {
                        $response->setContent(json_encode($content['responseData']));
                    } else {
                        $response->setContent($content['responseData']);
                    }
                }
            }
        } catch (\Throwable $exception) {
        }
        return $response;
    }
}