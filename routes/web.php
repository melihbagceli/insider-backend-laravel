<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'backend-laravel',
        'status' => 'ok',
    ]);
});
