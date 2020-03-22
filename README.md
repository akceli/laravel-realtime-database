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

```

