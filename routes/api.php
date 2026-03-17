<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\V1\DriverController;
use App\Http\Controllers\Api\V1\MobileController;
use App\Http\Controllers\Api\V1\PublicController;
use App\Http\Controllers\Api\V1\SalesController;
use App\Http\Controllers\Api\V1\VehicleProfitabilityController;

Route::post('login', [AuthController::class,'login'])->name('login');
Route::post('v1/public/contact', [PublicController::class, 'contact'])->name('api.public.contact');

Route::group(['prefix' => 'v1', 'as' => 'api.', 'namespace' => 'Api\V1\Admin', 'middleware' => ['auth:sanctum']], function () {
    Route::get('sales-by-week/{date}', [SalesController::class,'salesByWeek'])->name('salesByWeek');
    Route::get('vehicle-profitabilities', [VehicleProfitabilityController::class, 'index'])->name('vehicleProfitabilities');
    Route::get('drivers', [DriverController::class, 'index'])->name('drivers');
    Route::prefix('mobile')->name('mobile.')->group(function () {
        Route::get('me', [MobileController::class, 'me'])->name('me');
        Route::get('dashboard', [MobileController::class, 'dashboard'])->name('dashboard');
    });
});
