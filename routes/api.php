<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\V1\CompanyReportApiController;
use App\Http\Controllers\Api\V1\ContaAzulController;
use App\Http\Controllers\Api\V1\DriverController;
use App\Http\Controllers\Api\V1\MobileController;
use App\Http\Controllers\Api\V1\MobileInspectionController;
use App\Http\Controllers\Api\V1\PublicController;
use App\Http\Controllers\Api\V1\SalesController;
use App\Http\Controllers\Api\V1\VehicleProfitabilityController;

Route::post('login', [AuthController::class,'login'])->name('login');
Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('forgotPassword');
Route::post('v1/public/contact', [PublicController::class, 'contact'])->name('api.public.contact');

Route::group(['prefix' => 'v1', 'as' => 'api.', 'namespace' => 'Api\V1\Admin', 'middleware' => ['auth:sanctum']], function () {
    Route::get('sales-by-week/{date}', [SalesController::class,'salesByWeek'])->name('salesByWeek');
    Route::get('vehicle-profitabilities', [VehicleProfitabilityController::class, 'index'])->name('vehicleProfitabilities');
    Route::get('drivers', [DriverController::class, 'index'])->name('drivers');
    Route::get('vehicle-usages', [MobileController::class, 'vehicleUsages'])->name('vehicleUsages');
    Route::get('weeks', [MobileController::class, 'weeks'])->name('weeks');
    Route::get('company-reports/weekly', [CompanyReportApiController::class, 'weekly'])->name('companyReports.weekly');
    Route::prefix('conta-azul')->name('contaAzul.')->group(function () {
        Route::get('status', [ContaAzulController::class, 'status'])->name('status');
        Route::get('accounts', [ContaAzulController::class, 'accounts'])->name('accounts');
        Route::get('balances', [ContaAzulController::class, 'balances'])->name('balances');
        Route::get('categories', [ContaAzulController::class, 'categories'])->name('categories');
        Route::get('receivables', [ContaAzulController::class, 'receivables'])->name('receivables');
        Route::get('payables', [ContaAzulController::class, 'payables'])->name('payables');
        Route::prefix('manager')->name('manager.')->group(function () {
            Route::get('profit-loss', [ContaAzulController::class, 'managerProfitLoss'])->name('profitLoss');
            Route::get('movements', [ContaAzulController::class, 'managerMovements'])->name('movements');
            Route::get('expenses', [ContaAzulController::class, 'managerExpenses'])->name('expenses');
        });
    });
    Route::prefix('mobile')->name('mobile.')->group(function () {
        Route::get('me', [MobileController::class, 'me'])->name('me');
        Route::get('dashboard', [MobileController::class, 'dashboard'])->name('dashboard');
        Route::prefix('inspections')->name('inspections.')->group(function () {
            Route::get('/', [MobileInspectionController::class, 'index'])->name('index');
            Route::get('create-options', [MobileInspectionController::class, 'createOptions'])->name('createOptions');
            Route::post('/', [MobileInspectionController::class, 'store'])->name('store');
            Route::get('{inspection}', [MobileInspectionController::class, 'show'])->name('show');
            Route::post('{inspection}/step', [MobileInspectionController::class, 'updateStep'])->name('updateStep');
            Route::post('{inspection}/back-step', [MobileInspectionController::class, 'backStep'])->name('backStep');
            Route::post('{inspection}/damages/{damage}/resolve', [MobileInspectionController::class, 'resolveDamage'])->name('resolveDamage');
            Route::post('{inspection}/close', [MobileInspectionController::class, 'close'])->name('close');
        });
        Route::prefix('driver')->name('driver.')->group(function () {
            Route::get('weeks', [MobileController::class, 'driverWeeks'])->name('weeks');
            Route::get('receipts', [MobileController::class, 'driverReceipts'])->name('receipts');
            Route::post('receipts', [MobileController::class, 'storeDriverReceipt'])->name('receipts.store');
            Route::post('expense-receipts', [MobileController::class, 'storeDriverExpenseReceipt'])->name('expenseReceipts.store');
            Route::post('reimbursements', [MobileController::class, 'storeDriverReimbursement'])->name('reimbursements.store');
            Route::get('documents', [MobileController::class, 'driverDocuments'])->name('documents');
        });
    });
});
