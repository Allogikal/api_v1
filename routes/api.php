<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// GENERAL ROUTES
Route::get('/products', [ProductController::class, 'index']);
Route::post('/signup', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// PROTECTED ROUTES
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/{id}', [CartController::class, 'store']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);
});

// PROTECTED ROUTES OF ADMIN
Route::group(['middleware' => ['admin', 'auth:sanctum']], function () {
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/product/{id}', [ProductController::class, 'update']);
    Route::delete('/product/{id}', [ProductController::class, 'destroy']);
});
