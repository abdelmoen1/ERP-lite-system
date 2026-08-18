<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerDebtController;
use App\Http\Controllers\Api\DebtController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\AuthController;

Route::apiResource('/customers', CustomerController::class);

Route::apiResource('/invoices', InvoiceController::class);

Route::apiResource('/debts', DebtController::class);

Route::apiResource('/payments', PaymentController::class);

Route::get('/customers/{customer}/debts', [CustomerDebtController::class, 'index']);

Route::get('/debts/{debt}/details', [DebtController::class, 'details']);

Route::post('/customers/{customer}/debts/pay-all', [CustomerDebtController::class, 'payAll']);

Route::post('/payments/{payment}/reverse', [PaymentController::class, 'is_reverse']);

Route::post('/payment-groups/{paymentGroupId}/reverse', [PaymentController::class, 'reverseGroup']);

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum', 'store'])->get('/my-store', function (Request $request) {
    return response()->json([
        'user' => $request->user()->name,
        'store' => $request->user()->store,
    ]);
});
