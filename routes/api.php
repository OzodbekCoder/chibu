<?php

use App\Http\Controllers\DeviceTokenController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::post('/device-token', [DeviceTokenController::class, 'store']);
});
