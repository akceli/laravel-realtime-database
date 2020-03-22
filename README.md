# Akceli Realtime Client Store Sync

### Publish Assets
Publish the Akceli\RealtimeClientStoreSync\ServiceProvider
```bash
php artisan vendor:publish

```

### Add the route to your routes file
```php
\Akceli\RealtimeClientStoreSync\ClientStore\ClientStoreController::apiRoutes();

```

### Add the ClientStoreModel Trait to any model you with to track
```php


```