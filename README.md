# Laravel Realtime Database
Provides a simple way to fully sync a client store across many browsers.  No more manually
firing events on the api, and manually handing each event on the front end.

## How It Works
* Buy Flagging changes to Models, we are able to track changes indirectly.
* Buy Defining a Store on the server we know what the client expects.
* Buy Defining properties on the store (Eloquent Queries) we can very accurately scope the data.
* Buy Defining Model Changes we can fine tune the way the data is modified
* Buy Leveraging Middleware, you can set it and forget it, data will stay in sync if you change the client or the backend.

##### Docs:  https://laravel-realtime-database.akceli.io/
