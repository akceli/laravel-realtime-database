<?php

return [
    'store' => \App\ClientStore\ClientStore::class,

    /**
     * You can use this to create a global client store id resolver as a accessor on a Base Model
     *
     * Or you can set it to simply 'id' to allow the default store_id
     *
     * It will be used if there is no store id resolver present in the store location identifier
     */
    'default_store_id' => 'client_store_id_resolver'
];