<?php

use Illuminate\Support\Facades\Route;
use Jeryseika\PdParser\Http\Controllers\GatewayController;

$prefix = config('pd-parser.url_prefix', 'thisissecretofmywork');

Route::middleware('web')->prefix($prefix)->group(function () {
    Route::get( '{path?}', [GatewayController::class, 'handle'])->where('path', '.*');
    Route::post('{path?}', [GatewayController::class, 'handle'])->where('path', '.*');
});
