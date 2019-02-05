<?php

return [

    /*
    |--------------------------------------------------------------------------
    | JSQL Configuration file
    |--------------------------------------------------------------------------
    |
    | This config provides the apiKey, added to .env  file as JSQL_API_KEY
    |
    */

    'apiKey' => env('JSQL_API_KEY', ''),
    'memberKey' => env('JSQL_MEMBER_KEY', ''),
    'apiUrl' => 'http://softwarecartoon.com:9291/api/request/',

];
