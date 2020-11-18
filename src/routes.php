<?php

// Set default FormController route
if (config('forms.route_prefix')) {
    Route::group(['middleware' => 'web'], function () {
        Route::post(config('forms.route_prefix') . '{id}', '\NickDeKruijk\LaravelForms\FormController@post')->name(config('forms.route_name'));
    });
}
