# Akceli Realtime Client Store Sync

### Publish Assets
Publish the Akceli\RealtimeClientStoreSync\ServiceProvider
```bash
php artisan vendor:publish

```

### Register the Middleware
    
```
File: app/Http/Kernel.php

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        ...
        'client-store' => FlushClientStoreChangesMiddleware::class,
        ...
    ];
```

### Add the route to your routes file
```php
File: routes/api.php

\Akceli\RealtimeClientStoreSync\ClientStore\ClientStoreController::apiRoutes();

Route::middleware(['auth:api', 'client-store'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});


```

### Add the ClientStoreModel Trait to any model you with to track
```

use App\ClientStore\ClientStoreModelTrait;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use ClientStoreModelTrait;
    use Notifiable;

    public $store_locations = ['users.users'];

```


### Add the testing api overrides
since the middleware send client store changes along side the response
you will need to remove the client store changes for your tests.  Just use the and trait
and tests will continue to work as expected.
```
File: tests\TestCase.php

<?php

namespace Tests;

use Akceli\RealtimeClientStoreSync\Middleware\ClientStoreTestMiddlewareOverwrites;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseTransactions;
    use ClientStoreTestMiddlewareOverwrites;
}

```

