<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'La Cuponera SV API',
        'api' => url('/api/v1'),
    ]);
});
