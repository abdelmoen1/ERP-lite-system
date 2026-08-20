<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerDebtController;
use App\Http\Controllers\Api\DebtController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\InvitationController;


// Authentication
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register/invitation', [AuthController::class, 'acceptInvitation']);


// Protected API
Route::middleware(['auth:sanctum', 'store'])->group(function () {

    // Current authenticated user
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/invitations', [InvitationController::class, 'store']);

    // Current user's store
    Route::get('/my-store', function (Request $request) {
        return response()->json([
            'user' => $request->user()->name,
            'store' => $request->user()->store,
        ]);
    });


    // Customers
    Route::apiResource('customers', CustomerController::class);

    Route::get(
        '/customers/{customer}/debts',
        [CustomerDebtController::class, 'index']
    );

    Route::post(
        '/customers/{customer}/debts/pay-all',
        [CustomerDebtController::class, 'payAll']
    );


    // Invoices
    Route::apiResource('invoices', InvoiceController::class);


    // Debts
    Route::apiResource('debts', DebtController::class);

    Route::get(
        '/debts/{debt}/details',
        [DebtController::class, 'details']
    );


    // Payments
    Route::apiResource('payments', PaymentController::class);

    Route::post(
        '/payments/{payment}/reverse',
        [PaymentController::class, 'is_reverse']
    );

    Route::post(
        '/payment-groups/{paymentGroupId}/reverse',
        [PaymentController::class, 'reverseGroup']
    );
});
