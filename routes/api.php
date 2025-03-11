<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\WarehouseController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\ProductMovementController;

// Склады
Route::get('/warehouses', [WarehouseController::class, 'index']);
Route::get('/warehouses/{id}', [WarehouseController::class, 'show']);

// Товары
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// Заказы
Route::get('/orders', [OrderController::class, 'index']);
Route::get('/orders/{id}', [OrderController::class, 'show']);
Route::post('/orders', [OrderController::class, 'store']);
Route::put('/orders/{id}', [OrderController::class, 'update']);
Route::patch('/orders/{id}/complete', [OrderController::class, 'complete']);
Route::patch('/orders/{id}/cancel', [OrderController::class, 'cancel']);
Route::patch('/orders/{id}/resume', [OrderController::class, 'resume']);

// Движения товаров
Route::get('/product-movements', [ProductMovementController::class, 'index']);