<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\V1\SalesController;

Route::post('login', [AuthController::class,'login'])->name('login');

Route::group(['prefix' => 'v1', 'as' => 'api.', 'namespace' => 'Api\V1\Admin', 'middleware' => ['auth:sanctum']], function () {
    Route::get('sales-by-week/{date}', [SalesController::class,'salesByWeek'])->name('salesByWeek');
});
