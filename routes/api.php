<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\SyncController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/pos/sync-pull', [SyncController::class, 'pull'])->name('api.pos.sync-pull');
    Route::post('/pos/sync-push', [SyncController::class, 'push'])->name('api.pos.sync-push');

    // Stripe Mock Payment Intent
    Route::post('/payment/stripe-intent', function (Request $request) {
        $request->validate([
            'amount' => 'required|numeric|min:0.5',
            'currency' => 'required|string',
        ]);
        
        return response()->json([
            'success' => true,
            'client_secret' => 'pi_mock_' . \Illuminate\Support\Str::random(24) . '_secret_' . \Illuminate\Support\Str::random(24),
            'amount' => $request->amount,
            'message' => 'Stripe Mock Intent generated successfully'
        ]);
    });

    // bKash Mock Transaction Verification
    Route::post('/payment/bkash-verify', function (Request $request) {
        $request->validate([
            'trx_id' => 'required|string|min:8',
            'amount' => 'required|numeric',
        ]);

        $trxId = strtoupper($request->trx_id);

        return response()->json([
            'success' => true,
            'trx_id' => $trxId,
            'amount' => $request->amount,
            'sender' => '01712' . rand(100000, 999999),
            'verified_at' => now()->toIso8601String(),
            'message' => 'bKash Transaction verified successfully'
        ]);
    });
});
