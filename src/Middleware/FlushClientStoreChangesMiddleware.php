<?php

namespace Akceli\RealtimeClientStoreSync\Middleware;

use Akceli\RealtimeClientStoreSync\PusherService\PusherService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Class FlushPusherQueueMiddleware.
 *
 * Documentation: https://laravel.com/docs/5.8/middleware#defining-middleware
 *
 *
 *
 *
 *
 *              WILL BREAK IF USED ON A FILE DOWNLOAD ROUTE
 *
 *
 *
 *
 */
class FlushClientStoreChangesMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /** @var JsonResponse $response */
        $response = $next($request);

        $content = json_decode($response->getContent(), true);
        $content = (json_last_error() == JSON_ERROR_NONE) ? $content : $response->getContent();

        if (env('APP_ENV') === 'local') {
            DB::enableQueryLog();
        }
        
        $data = [
            'responseData' => $content,
            'clientStoreChanges' => PusherService::flushQueue(),
        ];
        
        if (env('APP_ENV') === 'local') {
            $data['pusherDataSyncQueries'] =  DB::getQueryLog();
        }
        
        $response->setContent(json_encode($data));

        return $response;
    }
}
