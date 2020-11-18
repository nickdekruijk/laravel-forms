<?php

return [

    /*
    |--------------------------------------------------------------------------
    | route_prefix
    |--------------------------------------------------------------------------
    | FormController uses a route bases on a unique id and prefixed with form_
    */
    'route_prefix' => 'form_',

    /*
    |--------------------------------------------------------------------------
    | route_name
    |--------------------------------------------------------------------------
    | The default name for the FormController route
    */
    'route_name' => 'FormController',

    /*
    |--------------------------------------------------------------------------
    | session_prefix
    |--------------------------------------------------------------------------
    | Form data will be stored in a session variable, to avoid conflicts this
    | variable will be prefixed with form_
    */
    'session_prefix' => 'form_',

];
