<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\V1\DriverController;
use App\Http\Controllers\Api\V1\SalesController;
use App\Http\Controllers\Api\V1\VehicleProfitabilityController;

Route::post('login', [AuthController::class,'login'])->name('login');

Route::group(['prefix' => 'v1', 'as' => 'api.', 'namespace' => 'Api\V1\Admin', 'middleware' => ['auth:sanctum']], function () {
    Route::get('sales-by-week/{date}', [SalesController::class,'salesByWeek'])->name('salesByWeek');
    Route::get('vehicle-profitabilities', [VehicleProfitabilityController::class, 'index'])->name('vehicleProfitabilities');
    Route::get('drivers', [DriverController::class, 'index'])->name('drivers');
});
