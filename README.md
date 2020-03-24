# Akceli Realtime Client Store Sync

### Require the composer package
```bash
composer require akceli/realtime-client-store-sync dev-master

```

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

// Dont forget include the Store api
\Akceli\RealtimeClientStoreSync\ClientStore\ClientStoreController::apiRoutes();

// Dont forget to add the middleware to the api routes
Route::middleware(['auth:api', 'client-store'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});


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


    //  This is the trait you need to add
    use ClientStoreTestMiddlewareOverwrites;
}

```

### Setup Your Client Store
File: app/ClientStore/ClientStore.php

Add the stores you want on the client, there is a users store example created by default

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

### Api Complete
Now all of your api changes will automatically update the client State if it applies the following middleware to all api call.


### Javascript code to handle the response
```javascript
// Http Middle Ware
function http() {
  const httpInstance = axios.create({
    baseURL: process.env.MIX_BASE_API_URL,
    headers: {'Authorization': `Bearer ${LocalStorageService.getApiToken()}`}
  });

  httpInstance.interceptors.response.use(
    response => successHandler(response),
    error => errorHandler(error),
  );

  return httpInstance;
}

const successHandler = (response) => {
  // If the client store changes are present, then process them
  if (response.data.clientStoreChanges) {
    response.data.clientStoreChanges.forEach(change => {
      store.dispatch('pusherEvent', change);
    });

    // Delete the clint store changes bacause the rest of the app should not care about it.
    delete response.data.clientStoreChanges;
  }
  
  // Making sure that the responseData gets formatted the same as before we put the
  // ClientStore Middleware on the api.
  if (response.data.responseData) {
    response.data = response.data.responseData;
  }
  return response;
};

//  Vue.js Actions
const actions = {
  pusherEvent({state, commit}, payload) {
    setTimeout(() => {
      if (payload.data) {
        commit(payload.method, payload);
      } else {
        http().get(payload.api_call).then(res => {
          payload.data = res.data;
          commit(payload.method, payload);
        }); 
      }

    }, payload.delay);
  },
};

//  Vue.js Mutations
const mutations = {
  updateInCollection(state, payload) {
    state[payload.store][payload.prop] = state[payload.store][payload.prop].map(item => item.id === payload.data.id ? {...item, ...payload.data} : item);
  },
  addToCollection(state, payload) {
    let collection = state[payload.store][payload.prop];
    if (!collection.some(item => item.id === payload.data.id)) {
      collection.push(payload.data);
    }
  },
  upsertCollection(state, payload) {
    let collection = state[payload.store][payload.prop];
    if (!collection.some(item => item.id === payload.data.id)) {
      collection.push(payload.data);
    } else {
      state[payload.store][payload.prop] = state[payload.store][payload.prop].map(item => item.id === payload.data.id ? {...item, ...payload.data} : item);
    }
  },
  removeFromCollection(state, payload) {
    state[payload.store][payload.prop] = state[payload.store][payload.prop].filter(item => item.id !== payload.data.id);
  },
  setRoot(state, payload) {
    state[payload.store][payload.prop] = payload.data;
  },
}


```

