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

    /*
    |--------------------------------------------------------------------------
    | upload_storage
    |--------------------------------------------------------------------------
    | Storage disk to use for uploads as defined in config/filesystems.php
    | For example 'local', 'public' or 's3'
    */
    'upload_storage' => 'local',

    /*
    |--------------------------------------------------------------------------
    | upload_path
    |--------------------------------------------------------------------------
    | Path to use within the upload storage
    */
    'upload_path' => 'form_uploads',

];
