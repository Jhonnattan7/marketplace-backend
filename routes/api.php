<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

// ── Autenticación ────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// ── Productos (público) ──────────────────────────────────────
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// ── Rutas protegidas ─────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Productos (vendedor)
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    Route::get('/seller/products', [ProductController::class, 'myProducts']);
    Route::get('/seller/products/deleted', [ProductController::class, 'deletedProducts']);

    // Pedidos (comprador)
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);

    // Pedidos (vendedor)
    Route::get('/seller/orders', [OrderController::class, 'sellerOrders']);
    Route::put('/seller/orders/{order}/status', [OrderController::class, 'updateStatus']);

    // Pagos
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::get('/payments/{payment}', [PaymentController::class, 'show']);
});