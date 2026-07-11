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

    // Mock Order Lookup for Returns
    Route::get('/pos/orders/lookup', function (Request $request) {
        $request->validate(['offline_id' => 'required|string']);
        
        // Simulates returning a mocked previous purchase order
        return response()->json([
            'success' => true,
            'order' => [
                'offline_id' => $request->offline_id,
                'total_amount' => 120.00,
                'subtotal' => 100.00,
                'tax_amount' => 15.00,
                'discount_amount' => 5.00,
                'payment_method' => 'cash',
                'created_at' => now()->subDays(2)->toIso8601String(),
                'items' => [
                    [
                        'product_id' => 1,
                        'name' => 'Organic Honey 500g',
                        'quantity' => 2,
                        'price' => 45.00,
                        'subtotal' => 90.00
                    ],
                    [
                        'product_id' => 2,
                        'name' => 'Premium Basmati Rice 5kg',
                        'quantity' => 1,
                        'price' => 30.00,
                        'subtotal' => 30.00
                    ]
                ]
            ]
        ]);
    });

    // Mock Email/SMS Receipts
    Route::post('/receipt/email', function (Request $request) {
        $request->validate(['email' => 'required|email', 'offline_id' => 'required|string']);
        return response()->json(['success' => true, 'message' => 'Receipt emailed successfully!']);
    });

    Route::post('/receipt/sms', function (Request $request) {
        $request->validate(['phone' => 'required|string', 'offline_id' => 'required|string']);
        return response()->json(['success' => true, 'message' => 'Receipt sent via SMS successfully!']);
    });
});
